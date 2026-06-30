<?php

namespace Tests\Unit\Services;

use App\Clients\VoyageClient;
use App\Models\Chunk;
use App\Models\Document;
use App\Services\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EmbeddingServiceTest extends TestCase
{
    use RefreshDatabase;

    // embedAndSave()がチャンクをDBに保存する
    public function test_embed_and_save_creates_chunks(): void
    {
        $document    = Document::factory()->create();
        $dummyVector = array_fill(0, 1024, 0.1);
        $chunks      = ['チャンク1のテキスト', 'チャンク2のテキスト'];

        // VoyageClientをモックしてAPIを叩かずにダミーベクトルを返す
        $voyageClient = $this->createMock(VoyageClient::class);
        $voyageClient->expects($this->exactly(count($chunks)))
            ->method('embed')
            ->willReturn($dummyVector);

        $service = new EmbeddingService($voyageClient);
        $service->embedAndSave($document, $chunks);

        // DBに正しい件数のChunkが保存されているか確認する
        $this->assertDatabaseCount('chunks', 2);
        $this->assertDatabaseHas('chunks', [
            'document_id' => $document->id,
            'content'     => 'チャンク1のテキスト',
            'chunk_index' => 0,
        ]);
        $this->assertDatabaseHas('chunks', [
            'document_id' => $document->id,
            'content'     => 'チャンク2のテキスト',
            'chunk_index' => 1,
        ]);
    }

    // VoyageClientが例外を投げた場合はトランザクションがロールバックされる
    public function test_embed_and_save_rolls_back_on_voyage_error(): void
    {
        $document = Document::factory()->create();

        $voyageClient = $this->createMock(VoyageClient::class);
        $voyageClient->method('embed')
            ->willThrowException(new \RuntimeException('API error'));

        $service = new EmbeddingService($voyageClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API error');

        $service->embedAndSave($document, ['テスト']);

        // ロールバックされてChunkが保存されていないことを確認する
        $this->assertDatabaseCount('chunks', 0);
    }

    // embedAndSave()完了後にログが出力される
    public function test_embed_and_save_logs_on_success(): void
    {
        $document    = Document::factory()->create();
        $dummyVector = array_fill(0, 1024, 0.1);

        $voyageClient = $this->createMock(VoyageClient::class);
        $voyageClient->method('embed')->willReturn($dummyVector);

        Log::spy();

        $service = new EmbeddingService($voyageClient);
        $service->embedAndSave($document, ['テスト']);

        Log::shouldHaveReceived('info')
            ->with('チャンクのベクトル化と保存が完了しました', \Mockery::any())
            ->once();
    }
}
