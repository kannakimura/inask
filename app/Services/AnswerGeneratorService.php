<?php

namespace App\Services;

use App\Clients\ClaudeClient;
use App\DTOs\AnswerResult;
use App\DTOs\SearchResult;

class AnswerGeneratorService
{
    public function __construct(private ClaudeClient $claudeClient)
    {
    }

    // SearchResultの配列を文脈としてClaudeに渡しRAG回答を生成して返す
    // 検索結果が0件の場合はClaudeを呼ばずに即座に例外を投げる
    // @param SearchResult[] $sources
    public function generate(string $query, array $sources): AnswerResult
    {
        if (empty($sources)) {
            throw new \InvalidArgumentException(config('errors.answer_generator.no_sources'));
        }

        $prompt  = $this->buildPrompt($query, $sources);
        $answer  = $this->claudeClient->generate($prompt);

        return new AnswerResult(
            answer:  $answer,
            sources: $sources,
        );
    }

    // クエリと検索結果からRAG用プロンプトを組み立てる
    // @param SearchResult[] $sources
    private function buildPrompt(string $query, array $sources): string
    {
        // 出典ごとにドキュメントタイトルを付けて文脈として提示する
        $contextParts = [];
        foreach ($sources as $i => $source) {
            $contextParts[] = "【出典 " . ($i + 1) . ": {$source->documentTitle}】\n{$source->content}";
        }
        $context = implode("\n\n", $contextParts);

        return <<<PROMPT
以下の社内ドキュメントの内容をもとに、質問に答えてください。

## 参考ドキュメント

{$context}

## 質問

{$query}

## 回答ルール

- 必ず上記の参考ドキュメントに記載されている情報のみをもとに回答してください
- 参考ドキュメントに記載がない内容については「提供されたドキュメントには記載がありません」と答えてください
- 回答は日本語で、簡潔かつ正確に記述してください
- 箇条書きや見出しを使って読みやすく整形してください
PROMPT;
    }
}
