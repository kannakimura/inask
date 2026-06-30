<?php

namespace App\DTOs;

// RAG回答の結果を保持するDTO
readonly class AnswerResult
{
    public function __construct(
        // Claudeが生成した回答テキスト
        public string $answer,
        // 回答の根拠となった検索結果（出典表示用）
        // @var SearchResult[]
        public array $sources,
    ) {
    }
}
