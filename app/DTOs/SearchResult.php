<?php

namespace App\DTOs;

// ベクトル検索の1件分の結果を保持するDTO
readonly class SearchResult
{
    public function __construct(
        // チャンクID
        public int $chunkId,
        // チャンクが属するドキュメントID
        public int $documentId,
        // ドキュメントのタイトル（出典表示用）
        public string $documentTitle,
        // チャンクのテキスト内容
        public string $content,
        // クエリとのコサイン距離（0に近いほど類似度が高い）
        public float $distance,
    ) {
    }
}
