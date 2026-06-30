<?php

namespace App\Services;

use App\Clients\VoyageClient;
use App\DTOs\SearchResult;
use Illuminate\Support\Facades\DB;

class SearchService
{
    public function __construct(private VoyageClient $voyageClient)
    {
    }

    // クエリテキストをベクトル化してpgvectorのコサイン距離でtop_k件のChunkを返す
    // @return SearchResult[]
    public function search(string $query): array
    {
        $topK = config('inask.search.top_k', 5);

        // クエリを1件だけembedする（embedBatchは配列を受け取るため1要素配列で渡す）
        $embeddings = $this->voyageClient->embedBatch([$query]);
        $vector     = $embeddings[0];

        // pgvectorが受け付けるフォーマット "[0.1,0.2,...]" に変換する
        $vectorLiteral = '[' . implode(',', $vector) . ']';

        // <=> はpgvectorのコサイン距離演算子（0=完全一致、2=完全逆方向）
        // コサイン距離が小さい順でtop_k件取得する
        $rows = DB::select(
            <<<SQL
            SELECT
                chunks.id           AS chunk_id,
                chunks.document_id,
                chunks.document_title,
                chunks.content,
                embedding <=> ?::vector AS distance
            FROM chunks
            ORDER BY distance ASC
            LIMIT ?
            SQL,
            [$vectorLiteral, $topK],
        );

        return array_map(fn($row) => new SearchResult(
            chunkId:       $row->chunk_id,
            documentId:    $row->document_id,
            documentTitle: $row->document_title,
            content:       $row->content,
            distance:      (float) $row->distance,
        ), $rows);
    }
}
