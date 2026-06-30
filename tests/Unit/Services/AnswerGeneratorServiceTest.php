<?php

namespace Tests\Unit\Services;

use App\Clients\ClaudeClient;
use App\DTOs\AnswerResult;
use App\DTOs\SearchResult;
use App\Services\AnswerGeneratorService;
use Tests\TestCase;

class AnswerGeneratorServiceTest extends TestCase
{
    // テスト用のSearchResultを生成するヘルパー
    private function makeSearchResult(
        string $content = 'チャンクの内容',
        string $documentTitle = 'テスト資料.pdf',
        int $documentId = 1,
        int $chunkId = 1,
        float $distance = 0.1,
    ): SearchResult {
        return new SearchResult(
            chunkId:       $chunkId,
            documentId:    $documentId,
            documentTitle: $documentTitle,
            content:       $content,
            distance:      $distance,
        );
    }

    // generate()がAnswerResultを返す
    public function test_generate_returns_answer_result(): void
    {
        $claudeClient = $this->createMock(ClaudeClient::class);
        $claudeClient->expects($this->once())
            ->method('generate')
            ->willReturn('これが回答です。');

        $sources = [$this->makeSearchResult()];
        $service = new AnswerGeneratorService($claudeClient);
        $result  = $service->generate('質問文', $sources);

        $this->assertInstanceOf(AnswerResult::class, $result);
        $this->assertSame('これが回答です。', $result->answer);
        $this->assertSame($sources, $result->sources);
    }

    // sourcesが空の場合はClaudeを呼ばずに例外を投げる
    public function test_generate_throws_when_sources_are_empty(): void
    {
        $claudeClient = $this->createMock(ClaudeClient::class);
        $claudeClient->expects($this->never())->method('generate');

        $service = new AnswerGeneratorService($claudeClient);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(config('errors.answer_generator.no_sources'));

        $service->generate('質問文', []);
    }

    // プロンプトにクエリとドキュメントタイトル・コンテキストが含まれる
    public function test_generate_includes_query_and_context_in_prompt(): void
    {
        $capturedPrompt = null;

        $claudeClient = $this->createMock(ClaudeClient::class);
        $claudeClient->method('generate')
            ->willReturnCallback(function (string $prompt) use (&$capturedPrompt) {
                $capturedPrompt = $prompt;
                return '回答';
            });

        $sources = [
            $this->makeSearchResult(content: '重要な内容A', documentTitle: '規程集.pdf'),
            $this->makeSearchResult(content: '重要な内容B', documentTitle: 'マニュアル.pdf', chunkId: 2, documentId: 2),
        ];

        $service = new AnswerGeneratorService($claudeClient);
        $service->generate('有給休暇の取り方は？', $sources);

        // クエリが含まれていることを確認する
        $this->assertStringContainsString('有給休暇の取り方は？', $capturedPrompt);
        // 各出典のタイトルとコンテンツが含まれていることを確認する
        $this->assertStringContainsString('規程集.pdf', $capturedPrompt);
        $this->assertStringContainsString('重要な内容A', $capturedPrompt);
        $this->assertStringContainsString('マニュアル.pdf', $capturedPrompt);
        $this->assertStringContainsString('重要な内容B', $capturedPrompt);
        // 「わからない場合はわからないと答える」制約が含まれていることを確認する
        $this->assertStringContainsString('記載がありません', $capturedPrompt);
    }

    // AnswerResultのsourcesに渡したSearchResultがそのまま入っている
    public function test_generate_result_contains_all_sources(): void
    {
        $claudeClient = $this->createMock(ClaudeClient::class);
        $claudeClient->method('generate')->willReturn('回答');

        $source1 = $this->makeSearchResult(chunkId: 1, documentId: 1);
        $source2 = $this->makeSearchResult(chunkId: 2, documentId: 2);
        $sources = [$source1, $source2];

        $service = new AnswerGeneratorService($claudeClient);
        $result  = $service->generate('質問', $sources);

        $this->assertCount(2, $result->sources);
        $this->assertSame($source1, $result->sources[0]);
        $this->assertSame($source2, $result->sources[1]);
    }

    // ClaudeClientが例外を投げた場合はそのまま再スローする
    public function test_generate_propagates_claude_client_exception(): void
    {
        $claudeClient = $this->createMock(ClaudeClient::class);
        $claudeClient->method('generate')
            ->willThrowException(new \RuntimeException('Claude API error'));

        $service = new AnswerGeneratorService($claudeClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Claude API error');

        $service->generate('質問', [$this->makeSearchResult()]);
    }
}
