<?php

namespace App\Services;

use App\Clients\ClaudeClient;
use App\Models\Document;
use App\Models\Faq;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FaqGeneratorService
{
    public function __construct(private ClaudeClient $claudeClient)
    {
    }

    // チャンクテキスト群からFAQを生成してDBに保存する
    // 既存のFaqは削除してから新規保存する（再生成対応）
    public function generateAndSave(Document $document, array $chunkTexts): void
    {
        // 空チャンクで既存データを消してしまわないようガードする
        if (empty($chunkTexts)) {
            throw new \InvalidArgumentException(config('errors.faq_generator.empty_chunks'));
        }

        $faqCount = config('inask.claude.faq_count', 5);
        $prompt   = $this->buildPrompt($chunkTexts, $faqCount);

        // Claude APIにプロンプトを送ってJSON形式のFAQを取得する
        $raw = $this->claudeClient->generate($prompt);

        // レスポンスからJSONを抽出してパースする
        $faqs = $this->parseResponse($raw);

        // 全FAQ取得後に短いトランザクションでdelete/insertする
        DB::transaction(function () use ($document, $faqs) {
            // document行をロックして同一documentの並行再処理を直列化する
            Document::lockForUpdate()->findOrFail($document->id);

            // 既存FAQを削除してから保存する（再生成時の重複防止）
            $document->faqs()->delete();

            foreach ($faqs as $faq) {
                Faq::create([
                    'document_id' => $document->id,
                    'question'    => $faq['question'],
                    'answer'      => $faq['answer'],
                ]);
            }
        });

        Log::info('FAQの生成と保存が完了しました', [
            'document_id' => $document->id,
            'faq_count'   => count($faqs),
        ]);
    }

    // チャンクテキスト群とFAQ件数からプロンプト文字列を組み立てる
    private function buildPrompt(array $chunkTexts, int $faqCount): string
    {
        // チャンクを区切り線で連結してドキュメント全体のコンテキストとして渡す
        $context = implode("\n\n---\n\n", $chunkTexts);

        return <<<PROMPT
以下のドキュメントの内容をもとに、社内FAQとして有用なQ&Aを{$faqCount}件生成してください。

## ドキュメント内容

{$context}

## 生成ルール

- ドキュメントに記載されている事実のみをもとにQ&Aを作成してください
- 質問はドキュメントを読んだことがない社員が実際に疑問に思いそな内容にしてください
- 回答はドキュメントの内容を正確に反映し、簡潔にまとめてください
- 質問・回答ともに日本語で記述してください

## 出力形式

必ず以下のJSON配列形式のみを出力してください。前後に説明文やコードブロック記号（\`\`\`）は含めないでください。

[
  {
    "question": "質問文",
    "answer": "回答文"
  }
]
PROMPT;
    }

    // Claude APIのレスポンステキストからFAQ配列をパースして返す
    // @return array<int, array{question: string, answer: string}>
    private function parseResponse(string $raw): array
    {
        // レスポンスにコードブロック（```json ... ```）が含まれる場合に除去する
        $json = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $json = preg_replace('/\s*```\s*$/m', '', $json);
        $json = trim($json ?? $raw);

        $decoded = json_decode($json, true);

        if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(config('errors.faq_generator.invalid_json'));
        }

        // 各要素にquestionとanswerが文字列として存在するか検証する
        foreach ($decoded as $item) {
            if (
                !is_array($item)
                || !isset($item['question'], $item['answer'])
                || !is_string($item['question'])
                || !is_string($item['answer'])
                || $item['question'] === ''
                || $item['answer'] === ''
            ) {
                throw new \RuntimeException(config('errors.faq_generator.invalid_format'));
            }
        }

        return $decoded;
    }
}
