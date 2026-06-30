<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Services\ChunkSplitterService;
use App\Services\EmbeddingService;
use App\Services\TextExtractorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessDocumentJobTest extends TestCase
{
    use RefreshDatabase;

    // handle()が正常に完了するとステータスがdoneになる
    public function test_handle_updates_status_to_done_on_success(): void
    {
        $document = Document::factory()->create([
            'status'    => config('inask.document_status.pending'),
            'mime_type' => 'text/plain',
            'file_path' => 'documents/test.txt',
        ]);

        // TextExtractorServiceをモックしてStorage依存を排除する
        $textExtractor = $this->createMock(TextExtractorService::class);
        $textExtractor->method('extract')->willReturn('テストコンテンツ');

        $chunkSplitter = $this->createMock(ChunkSplitterService::class);
        $chunkSplitter->method('split')->willReturn(['チャンク1']);

        $embeddingService = $this->createMock(EmbeddingService::class);
        $embeddingService->expects($this->once())->method('embedAndSave');

        $job = new ProcessDocumentJob($document);
        $job->handle($textExtractor, $chunkSplitter, $embeddingService);

        // ステータスがdoneに更新されているか確認する
        $this->assertSame(config('inask.document_status.done'), $document->fresh()->status);
    }

    // handle()はprocessing→doneの順でステータスを更新する
    public function test_handle_sets_processing_status_before_done(): void
    {
        $document = Document::factory()->create([
            'status'    => config('inask.document_status.pending'),
            'mime_type' => 'text/plain',
            'file_path' => 'documents/test.txt',
        ]);

        $statusDuringProcessing = null;

        $textExtractor = $this->createMock(TextExtractorService::class);
        // extract()呼び出し時点のステータスを記録する
        $textExtractor->method('extract')->willReturnCallback(function () use ($document, &$statusDuringProcessing) {
            $statusDuringProcessing = $document->fresh()->status;
            return 'テストコンテンツ';
        });

        $chunkSplitter = $this->createMock(ChunkSplitterService::class);
        $chunkSplitter->method('split')->willReturn(['チャンク1']);

        $embeddingService = $this->createMock(EmbeddingService::class);

        $job = new ProcessDocumentJob($document);
        $job->handle($textExtractor, $chunkSplitter, $embeddingService);

        // 処理中はprocessingになっていたことを確認する
        $this->assertSame(config('inask.document_status.processing'), $statusDuringProcessing);
        // 完了後はdoneになっていることを確認する
        $this->assertSame(config('inask.document_status.done'), $document->fresh()->status);
    }

    // 処理中に例外が発生した場合はステータスがfailedになり例外が再スローされる
    public function test_handle_updates_status_to_failed_on_error(): void
    {
        $document = Document::factory()->create([
            'status'    => config('inask.document_status.pending'),
            'mime_type' => 'text/plain',
            'file_path' => 'documents/test.txt',
        ]);

        $textExtractor = $this->createMock(TextExtractorService::class);
        $textExtractor->method('extract')->willThrowException(new \RuntimeException('抽出エラー'));

        $chunkSplitter    = $this->createMock(ChunkSplitterService::class);
        $embeddingService = $this->createMock(EmbeddingService::class);

        $job = new ProcessDocumentJob($document);

        try {
            $job->handle($textExtractor, $chunkSplitter, $embeddingService);
            $this->fail('例外が発生しませんでした');
        } catch (\RuntimeException $e) {
            $this->assertSame('抽出エラー', $e->getMessage());
        }

        // ステータスがfailedに更新されているか確認する
        $this->assertSame(config('inask.document_status.failed'), $document->fresh()->status);
    }

    // pending以外のstatusのDocumentはJobをスキップする（二重dispatch防止）
    public function test_handle_skips_when_status_is_not_pending(): void
    {
        foreach (['processing', 'done', 'failed'] as $status) {
            $document = Document::factory()->create([
                'status'    => $status,
                'mime_type' => 'text/plain',
                'file_path' => 'documents/test.txt',
            ]);

            $textExtractor = $this->createMock(TextExtractorService::class);
            // pending以外ではextract()が呼ばれないことを確認する
            $textExtractor->expects($this->never())->method('extract');

            $chunkSplitter    = $this->createMock(ChunkSplitterService::class);
            $embeddingService = $this->createMock(EmbeddingService::class);

            $job = new ProcessDocumentJob($document);
            $job->handle($textExtractor, $chunkSplitter, $embeddingService);

            // statusが変わっていないことを確認する
            $this->assertSame($status, $document->fresh()->status);
        }
    }

    // queueレベルの失敗（worker kill等）でもステータスがfailedになる
    public function test_failed_updates_status_to_failed(): void
    {
        $document = Document::factory()->create([
            'status' => config('inask.document_status.processing'),
        ]);

        $job = new ProcessDocumentJob($document);
        $job->failed(new \RuntimeException('workerがkillされました'));

        // failed()でもステータスがfailedに更新されているか確認する
        $this->assertSame(config('inask.document_status.failed'), $document->fresh()->status);
    }

    // store()後にProcessDocumentJobがdispatchされる
    public function test_store_dispatches_process_document_job(): void
    {
        Queue::fake();

        $file = \Illuminate\Http\UploadedFile::fake()->create('test.txt', 10, 'text/plain');

        $service  = app(\App\Services\DocumentService::class);
        $document = $service->store($file);

        Queue::assertPushed(ProcessDocumentJob::class, function ($job) use ($document) {
            // リフレクションでprivateプロパティを確認する
            $ref  = new \ReflectionClass($job);
            $prop = $ref->getProperty('document');
            $prop->setAccessible(true);
            return $prop->getValue($job)->id === $document->id;
        });
    }
}
