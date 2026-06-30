<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\ChunkSplitterService;
use App\Services\EmbeddingService;
use App\Services\TextExtractorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    // リトライしない（embedding APIを再呼び出しすると課金が重複するため）
    // 失敗時は管理者がドキュメントを再アップロードして対処する
    public int $tries = 1;

    // タイムアウト上限（秒）：大きなドキュメントでも最大1MBに制限しているため60秒で十分
    public int $timeout = 60;

    public function __construct(private Document $document)
    {
    }

    // アップロード済みドキュメントをチャンク分割→ベクトル化→pgvector保存する
    public function handle(
        TextExtractorService $textExtractor,
        ChunkSplitterService $chunkSplitter,
        EmbeddingService $embeddingService,
    ): void {
        // pendingの場合のみprocessingに更新する（原子的なCAS更新）
        // 更新件数が0の場合は既にprocessing/done/failedのため処理をスキップする
        // （二重dispatch時の重複課金を防ぐ）
        $updated = Document::where('id', $this->document->id)
            ->where('status', config('inask.document_status.pending'))
            ->update(['status' => config('inask.document_status.processing')]);

        if ($updated === 0) {
            Log::info(config('errors.process_document.skipped'), [
                'document_id' => $this->document->id,
                'status'      => $this->document->fresh()->status,
            ]);
            return;
        }

        try {
            // Storage keyをそのまま渡す（TextExtractorService内部で絶対パスに変換するため）
            $text = $textExtractor->extract($this->document->file_path, $this->document->mime_type);

            // テキストをチャンクに分割する
            $chunks = $chunkSplitter->split($text);

            // チャンクをベクトル化してpgvectorに保存する
            $embeddingService->embedAndSave($this->document, $chunks);

            // すべて完了したらステータスをdoneに更新する
            $this->document->update(['status' => config('inask.document_status.done')]);

            Log::info(config('errors.process_document.completed'), [
                'document_id' => $this->document->id,
                'chunk_count' => count($chunks),
            ]);
        } catch (\Throwable $e) {
            // 処理失敗時はステータスをfailedに更新してから例外を再スローする
            $this->document->update(['status' => config('inask.document_status.failed')]);

            Log::error(config('errors.process_document.failed'), [
                'document_id' => $this->document->id,
                'error'       => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // workerのタイムアウト・kill等のqueueレベル失敗時もステータスをfailedにする
    // （handle()のtry/catchが動かない経路でprocessingのまま残るのを防ぐ）
    public function failed(\Throwable $e): void
    {
        $this->document->update(['status' => config('inask.document_status.failed')]);

        Log::error(config('errors.process_document.failed'), [
            'document_id' => $this->document->id,
            'error'       => $e->getMessage(),
        ]);
    }
}
