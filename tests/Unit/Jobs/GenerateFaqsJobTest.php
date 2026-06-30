<?php

namespace Tests\Unit\Jobs;

use App\Jobs\GenerateFaqsJob;
use App\Models\Chunk;
use App\Models\Document;
use App\Models\Faq;
use App\Services\FaqGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GenerateFaqsJobTest extends TestCase
{
    use RefreshDatabase;

    // handle()がChunkテキストを取得してFaqGeneratorServiceを呼び出す
    public function test_handle_calls_faq_generator_with_chunk_texts(): void
    {
        $document = Document::factory()->create([
            'status' => config('inask.document_status.done'),
        ]);

        // chunk_index順のChunkを2件作成する
        Chunk::factory()->create([
            'document_id'    => $document->id,
            'document_title' => $document->title,
            'content'        => 'チャンク1の内容',
            'chunk_index'    => 0,
        ]);
        Chunk::factory()->create([
            'document_id'    => $document->id,
            'document_title' => $document->title,
            'content'        => 'チャンク2の内容',
            'chunk_index'    => 1,
        ]);

        // FaqGeneratorServiceをモックしてClaude API依存を排除する
        $faqGeneratorService = $this->createMock(FaqGeneratorService::class);
        // chunk_index順のテキスト配列で呼ばれることを確認する
        $faqGeneratorService->expects($this->once())
            ->method('generateAndSave')
            ->with($document, ['チャンク1の内容', 'チャンク2の内容']);

        $job = new GenerateFaqsJob($document);
        $job->handle($faqGeneratorService);
    }

    // Chunkが存在しない場合はFaqGeneratorServiceを呼ばずにスキップする
    public function test_handle_skips_when_no_chunks_exist(): void
    {
        $document = Document::factory()->create([
            'status' => config('inask.document_status.done'),
        ]);

        $faqGeneratorService = $this->createMock(FaqGeneratorService::class);
        // ChunkがないためgenerateAndSave()が呼ばれないことを確認する
        $faqGeneratorService->expects($this->never())->method('generateAndSave');

        Log::spy();

        $job = new GenerateFaqsJob($document);
        $job->handle($faqGeneratorService);

        // スキップのwarningログが出力されることを確認する
        Log::shouldHaveReceived('warning')
            ->with(config('errors.generate_faqs.skipped'), \Mockery::any())
            ->once();
    }

    // FaqGeneratorServiceが例外を投げた場合はログを残してから再スローする
    public function test_handle_rethrows_exception_from_faq_generator(): void
    {
        $document = Document::factory()->create([
            'status' => config('inask.document_status.done'),
        ]);

        Chunk::factory()->create([
            'document_id'    => $document->id,
            'document_title' => $document->title,
            'content'        => 'チャンク',
            'chunk_index'    => 0,
        ]);

        $faqGeneratorService = $this->createMock(FaqGeneratorService::class);
        $faqGeneratorService->method('generateAndSave')
            ->willThrowException(new \RuntimeException('FAQ生成エラー'));

        Log::spy();

        $job = new GenerateFaqsJob($document);

        try {
            $job->handle($faqGeneratorService);
            $this->fail('例外が発生しませんでした');
        } catch (\RuntimeException $e) {
            $this->assertSame('FAQ生成エラー', $e->getMessage());
        }

        // エラーログが出力されることを確認する
        Log::shouldHaveReceived('error')
            ->with(config('errors.generate_faqs.failed'), \Mockery::any())
            ->once();
    }

    // handle()完了後に完了ログが出力される
    public function test_handle_logs_on_success(): void
    {
        $document = Document::factory()->create([
            'status' => config('inask.document_status.done'),
        ]);

        Chunk::factory()->create([
            'document_id'    => $document->id,
            'document_title' => $document->title,
            'content'        => 'チャンク',
            'chunk_index'    => 0,
        ]);

        $faqGeneratorService = $this->createMock(FaqGeneratorService::class);
        // generateAndSave()の副作用としてFAQを作成する
        $faqGeneratorService->method('generateAndSave')->willReturnCallback(
            function (Document $doc) {
                Faq::factory()->count(3)->create(['document_id' => $doc->id]);
            }
        );

        Log::spy();

        $job = new GenerateFaqsJob($document);
        $job->handle($faqGeneratorService);

        Log::shouldHaveReceived('info')
            ->with(config('errors.generate_faqs.completed'), \Mockery::any())
            ->once();
    }

    // failed()がqueueレベルの失敗時にエラーログを出力する
    public function test_failed_logs_error(): void
    {
        $document = Document::factory()->create([
            'status' => config('inask.document_status.done'),
        ]);

        Log::spy();

        $job = new GenerateFaqsJob($document);
        $job->failed(new \RuntimeException('workerがkillされました'));

        Log::shouldHaveReceived('error')
            ->with(config('errors.generate_faqs.failed'), \Mockery::any())
            ->once();
    }

    // ProcessDocumentJobの完了後にGenerateFaqsJobがdispatchされる
    public function test_process_document_job_dispatches_generate_faqs_job(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $document = Document::factory()->create([
            'status'    => config('inask.document_status.pending'),
            'mime_type' => 'text/plain',
            'file_path' => 'documents/test.txt',
        ]);

        $textExtractor = $this->createMock(\App\Services\TextExtractorService::class);
        $textExtractor->method('extract')->willReturn('テストコンテンツ');

        $chunkSplitter = $this->createMock(\App\Services\ChunkSplitterService::class);
        $chunkSplitter->method('split')->willReturn(['チャンク1']);

        $embeddingService = $this->createMock(\App\Services\EmbeddingService::class);

        $job = new \App\Jobs\ProcessDocumentJob($document);
        $job->handle($textExtractor, $chunkSplitter, $embeddingService);

        // ProcessDocumentJobの完了後にGenerateFaqsJobがdispatchされることを確認する
        \Illuminate\Support\Facades\Queue::assertPushed(GenerateFaqsJob::class, function ($job) use ($document) {
            $ref  = new \ReflectionClass($job);
            $prop = $ref->getProperty('document');
            $prop->setAccessible(true);
            return $prop->getValue($job)->id === $document->id;
        });
    }
}
