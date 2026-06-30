<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\ChunkSplitterService;
use App\Services\EmbeddingService;
use App\Services\TextExtractorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    // 失敗時のリトライ上限（外部APIの一時的障害に対応するため3回）
    public int $tries = 3;

    public function __construct(private Document $document)
    {
    }

    // アップロード済みドキュメントをチャンク分割→ベクトル化→pgvector保存する
    public function handle(
        TextExtractorService $textExtractor,
        ChunkSplitterService $chunkSplitter,
        EmbeddingService $embeddingService,
    ): void {
        // ステータスをprocessingに更新してから処理を開始する
        $this->document->update(['status' => config('inask.document_status.processing')]);

        try {
            // ストレージからファイルの絶対パスを取得してテキストを抽出する
            $absolutePath = Storage::disk('local')->path($this->document->file_path);
            $text         = $textExtractor->extract($absolutePath, $this->document->mime_type);

            // テキストをチャンクに分割する
            $chunks = $chunkSplitter->split($text);

            // チャンクをベクトル化してpgvectorに保存する
            $embeddingService->embedAndSave($this->document, $chunks);

            // すべて完了したらステータスをdoneに更新する
            $this->document->update(['status' => config('inask.document_status.done')]);

            Log::info('ドキュメントの処理が完了しました', [
                'document_id' => $this->document->id,
                'chunk_count' => count($chunks),
            ]);
        } catch (\Throwable $e) {
            // 処理失敗時はステータスをfailedに更新してから例外を再スローする
            // （再スローすることでLaravelのリトライ・failed_jobsの仕組みが動く）
            $this->document->update(['status' => config('inask.document_status.failed')]);

            Log::error('ドキュメントの処理に失敗しました', [
                'document_id' => $this->document->id,
                'error'       => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
