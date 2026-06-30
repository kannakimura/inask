<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\FaqGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateFaqsJob implements ShouldQueue
{
    use Queueable;

    // リトライしない（Claude APIを再呼び出しすると課金が重複するため）
    // 失敗時は管理者がFAQ再生成を手動でトリガーして対処する
    public int $tries = 1;

    // タイムアウト上限（秒）
    // Claudeへのリクエスト（30秒上限） + JSONパース + DB保存のバッファを含め120秒に設定する
    public int $timeout = 120;

    public function __construct(private Document $document)
    {
    }

    // Chunkテキストを取得 → FaqGeneratorServiceでFAQ生成 → faqsテーブルに保存する
    public function handle(FaqGeneratorService $faqGeneratorService): void
    {
        // Chunkが存在しない場合はFAQを生成できないためスキップする
        // （ProcessDocumentJobの失敗後にdispatchされた場合の安全弁）
        $chunkTexts = $this->document->chunks()->orderBy('chunk_index')->pluck('content')->all();

        if (empty($chunkTexts)) {
            Log::warning(config('errors.generate_faqs.skipped'), [
                'document_id' => $this->document->id,
            ]);
            return;
        }

        try {
            $faqGeneratorService->generateAndSave($this->document, $chunkTexts);

            Log::info(config('errors.generate_faqs.completed'), [
                'document_id' => $this->document->id,
                'faq_count'   => $this->document->faqs()->count(),
            ]);
        } catch (\Throwable $e) {
            // 失敗時はログを残してから例外を再スローする
            // （ドキュメントのステータスはdone済みのため変更しない）
            Log::error(config('errors.generate_faqs.failed'), [
                'document_id' => $this->document->id,
                'error'       => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // workerのタイムアウト・kill等のqueueレベル失敗時もログを残す
    public function failed(\Throwable $e): void
    {
        Log::error(config('errors.generate_faqs.failed'), [
            'document_id' => $this->document->id,
            'error'       => $e->getMessage(),
        ]);
    }
}
