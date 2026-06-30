<?php

namespace Tests\Unit\Services;

use App\Clients\VoyageClient;
use App\DTOs\SearchResult;
use App\Models\Chunk;
use App\Models\Document;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    use RefreshDatabase;

    // search()がtop_k件のSearchResultを距離の昇順で返す
    public function test_search_returns_results_ordered_by_distance(): void
    {
        $document = Document::factory()->create(['status' => config('inask.document_status.done')]);

        // クエリに近いChunk（距離が小さくなるよう全要素を同じ値にする）
        $nearVector = '[' . implode(',', array_fill(0, 1024, 1.0)) . ']';
        $farVector  = '[' . implode(',', array_fill(0, 1024, -1.0)) . ']';

        $nearChunk = Chunk::factory()->create([
            'document_id'    => $document->id,
            'document_title' => $document->title,
            'content'        => '近いチャンク',
            'chunk_index'    => 0,
            'embedding'      => DB::raw("'{$nearVector}'::vector"),
        ]);
        $farChunk = Chunk::factory()->create([
            'document_id'    => $document->id,
            'document_title' => $document->title,
            'content'        => '遠いチャンク',
            'chunk_index'    => 1,
            'embedding'      => DB::raw("'{$farVector}'::vector"),
        ]);

        // クエリのembeddingを全要素1.0にする（nearChunkと同方向のため距離が小さい）
        $queryVector = array_fill(0, 1024, 1.0);

        $voyageClient = $this->createMock(VoyageClient::class);
        $voyageClient->expects($this->once())
            ->method('embedBatch')
            ->with(['テスト質問'])
            ->willReturn([$queryVector]);

        $service = new SearchService($voyageClient);
        $results = $service->search('テスト質問');

        // top_k=5だが登録は2件のため2件返る
        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(SearchResult::class, $results);

        // 距離の昇順（近い順）で返っていることを確認する
        $this->assertSame('近いチャンク', $results[0]->content);
        $this->assertSame('遠いチャンク', $results[1]->content);
        $this->assertLessThan($results[1]->distance, $results[0]->distance);
    }

    // SearchResultの各フィールドが正しく詰まっている
    public function test_search_result_has_correct_fields(): void
    {
        $document = Document::factory()->create([
            'title'  => 'テストドキュメント.pdf',
            'status' => config('inask.document_status.done'),
        ]);

        $vector = '[' . implode(',', array_fill(0, 1024, 0.5)) . ']';
        Chunk::factory()->create([
            'document_id'    => $document->id,
            'document_title' => $document->title,
            'content'        => 'チャンクの内容',
            'chunk_index'    => 0,
            'embedding'      => DB::raw("'{$vector}'::vector"),
        ]);

        $voyageClient = $this->createMock(VoyageClient::class);
        $voyageClient->method('embedBatch')->willReturn([array_fill(0, 1024, 0.5)]);

        $service = new SearchService($voyageClient);
        $results = $service->search('質問');

        $result = $results[0];
        $this->assertSame($document->id, $result->documentId);
        $this->assertSame('テストドキュメント.pdf', $result->documentTitle);
        $this->assertSame('チャンクの内容', $result->content);
        $this->assertIsFloat($result->distance);
    }

    // Chunkが1件もない場合は空配列を返す
    public function test_search_returns_empty_array_when_no_chunks(): void
    {
        $voyageClient = $this->createMock(VoyageClient::class);
        $voyageClient->expects($this->once())
            ->method('embedBatch')
            ->willReturn([array_fill(0, 1024, 0.1)]);

        $service = new SearchService($voyageClient);
        $results = $service->search('質問');

        $this->assertSame([], $results);
    }

    // top_k件を超えるChunkがある場合はtop_k件だけ返す
    public function test_search_returns_at_most_top_k_results(): void
    {
        config(['inask.search.top_k' => 2]);

        $document = Document::factory()->create(['status' => config('inask.document_status.done')]);
        $vector   = '[' . implode(',', array_fill(0, 1024, 0.1)) . ']';

        // top_k(2)を超える3件のChunkを作成する
        Chunk::factory()->count(3)->create([
            'document_id'    => $document->id,
            'document_title' => $document->title,
            'embedding'      => DB::raw("'{$vector}'::vector"),
        ]);

        $voyageClient = $this->createMock(VoyageClient::class);
        $voyageClient->method('embedBatch')->willReturn([array_fill(0, 1024, 0.1)]);

        $service = new SearchService($voyageClient);
        $results = $service->search('質問');

        // top_k=2件だけ返ることを確認する
        $this->assertCount(2, $results);
    }

    // VoyageClientが例外を投げた場合はそのまま再スローする
    public function test_search_propagates_voyage_client_exception(): void
    {
        $voyageClient = $this->createMock(VoyageClient::class);
        $voyageClient->method('embedBatch')
            ->willThrowException(new \RuntimeException('Voyage API error'));

        $service = new SearchService($voyageClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Voyage API error');

        $service->search('質問');
    }
}
