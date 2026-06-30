<?php

namespace App\Services;

use App\Clients\VoyageClient;
use App\Models\Chunk;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    public function __construct(private VoyageClient $voyageClient)
    {
    }

    // ドキュメントのチャンク配列をベクトル化してDBに保存する
    // 既存のChunkは削除してから新規保存する（再処理対応）
    public function embedAndSave(Document $document, array $chunks): void
    {
        // トランザクション外で全チャンクのembeddingを取得する
        // （外部APIをトランザクション内で待つとDB接続を長時間占有するため）
        $embeddings = [];
        foreach ($chunks as $index => $content) {
            $embeddings[$index] = $this->voyageClient->embed($content);
        }

        // 全embedding取得後に短いトランザクションでdelete/insertする
        DB::transaction(function () use ($document, $chunks, $embeddings) {
            // 既存チャンクを削除してから保存する（再処理時の重複防止）
            $document->chunks()->delete();

            foreach ($chunks as $index => $content) {
                $embedding = $embeddings[$index];

                // 各要素がfloatであることを検証する（外部APIの壊れたレスポンス対策）
                $validated = array_map(function ($v) {
                    if (!is_int($v) && !is_float($v)) {
                        throw new \RuntimeException(config('errors.voyage.invalid_response'));
                    }
                    return (float) $v;
                }, $embedding);

                // pgvectorが受け付けるフォーマット "[0.1,0.2,...]" に変換する
                $embeddingLiteral = '[' . implode(',', $validated) . ']';

                // Chunkレコードを保存する（embeddingはDB::rawでvector型として挿入）
                Chunk::create([
                    'document_id'    => $document->id,
                    'document_title' => $document->title,
                    'content'        => $content,
                    'chunk_index'    => $index,
                    'embedding'      => DB::raw("'{$embeddingLiteral}'::vector"),
                ]);
            }
        });

        Log::info('チャンクのベクトル化と保存が完了しました', [
            'document_id' => $document->id,
            'chunk_count' => count($chunks),
        ]);
    }
}
