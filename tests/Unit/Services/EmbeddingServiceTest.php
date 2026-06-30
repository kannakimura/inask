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

    // VoyageClientが例外を投げた場合はDBに保存されない
    public function test_embed_and_save_aborts_on_voyage_error(): void
    {
        $document = Document::factory()->create();

        $voyageClient = $this->createMock(VoyageClient::class);
        // embedding取得はトランザクション外で行うため、API失敗時はDBに何も保存されない
        $voyageClient->method('embed')
            ->willThrowException(new \RuntimeException('API error'));

        $service = new EmbeddingService($voyageClient);

        try {
            $service->embedAndSave($document, ['チャンク1']);
            $this->fail('例外が発生しませんでした');
        } catch (\RuntimeException $e) {
            $this->assertSame('API error', $e->getMessage());
        }

        // APIエラーでトランザクションに入る前に失敗するためChunkは保存されない
        $this->assertDatabaseCount('chunks', 0);
    }

    // embedが不正なレスポンス（非数値要素）を返した場合はトランザクションがロールバックされる
    public function test_embed_and_save_rolls_back_on_invalid_embedding(): void
    {
        $document = Document::factory()->create();

        $voyageClient = $this->createMock(VoyageClient::class);
        // 数値以外の要素を含む壊れたembeddingを返す
        $brokenVector = array_fill(0, 1023, 0.1);
        $brokenVector[] = 'invalid';
        $voyageClient->method('embed')->willReturn($brokenVector);

        $service = new EmbeddingService($voyageClient);

        try {
            $service->embedAndSave($document, ['チャンク1']);
            $this->fail('例外が発生しませんでした');
        } catch (\RuntimeException $e) {
            $this->assertSame(config('errors.voyage.invalid_response'), $e->getMessage());
        }

        // バリデーションエラーでロールバックされてChunkは保存されない
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
