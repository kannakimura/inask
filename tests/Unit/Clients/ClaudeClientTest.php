<?php

namespace Tests\Unit\Clients;

use App\Clients\ClaudeClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClaudeClientTest extends TestCase
{
    // generate()が正常なレスポンスからテキストを返す
    public function test_generate_returns_text_from_response(): void
    {
        config(['services.anthropic.api_key' => 'test-key']);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => 'FAQ生成結果のテキストです。'],
                ],
            ], 200),
        ]);

        $client = new ClaudeClient();
        $result = $client->generate('FAQを生成してください');

        $this->assertSame('FAQ生成結果のテキストです。', $result);
    }

    // APIキーが未設定の場合は例外を投げる
    public function test_generate_throws_exception_when_api_key_is_missing(): void
    {
        config(['services.anthropic.api_key' => '']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(config('errors.claude.api_key_missing'));

        $client = new ClaudeClient();
        $client->generate('テスト');
    }

    // APIがエラーを返した場合は例外を投げる
    public function test_generate_throws_exception_on_api_error(): void
    {
        config(['services.anthropic.api_key' => 'test-key']);

        // リトライが走らないよう常に401を返すシーケンスを設定する
        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push(['error' => ['type' => 'authentication_error', 'message' => 'Unauthorized']], 401)
                ->push(['error' => ['type' => 'authentication_error', 'message' => 'Unauthorized']], 401)
                ->push(['error' => ['type' => 'authentication_error', 'message' => 'Unauthorized']], 401),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(config('errors.claude.request_failed'));

        $client = new ClaudeClient();
        $client->generate('テスト');
    }

    // 接続失敗（タイムアウト・DNS障害など）の場合も同じ例外経路になる
    public function test_generate_throws_exception_on_connection_failure(): void
    {
        config(['services.anthropic.api_key' => 'test-key']);

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(config('errors.claude.request_failed'));

        $client = new ClaudeClient();
        $client->generate('テスト');
    }

    // contentが空配列のレスポンスの場合は不正レスポンスとして例外を投げる
    public function test_generate_throws_exception_on_empty_content(): void
    {
        config(['services.anthropic.api_key' => 'test-key']);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [],
            ], 200),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(config('errors.claude.invalid_response'));

        $client = new ClaudeClient();
        $client->generate('テスト');
    }

    // content[0].textが空文字のレスポンスの場合は不正レスポンスとして例外を投げる
    public function test_generate_throws_exception_on_empty_text(): void
    {
        config(['services.anthropic.api_key' => 'test-key']);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => ''],
                ],
            ], 200),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(config('errors.claude.invalid_response'));

        $client = new ClaudeClient();
        $client->generate('テスト');
    }

    // 429レートリミット後に成功する場合はリトライして結果を返す
    public function test_generate_retries_on_rate_limit_and_succeeds(): void
    {
        config(['services.anthropic.api_key' => 'test-key']);

        // 最初の1回は429、2回目で成功するシーケンスをモックする
        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push(['error' => ['type' => 'rate_limit_error', 'message' => 'Too Many Requests']], 429)
                ->push([
                    'content' => [
                        ['type' => 'text', 'text' => 'リトライ後の成功レスポンスです。'],
                    ],
                ], 200),
        ]);

        $client = new ClaudeClient();
        $result = $client->generate('テスト');

        $this->assertSame('リトライ後の成功レスポンスです。', $result);
    }
}
