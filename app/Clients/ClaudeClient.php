<?php

namespace App\Clients;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ClaudeClient
{
    private string $apiKey;
    private string $model;
    private int $maxTokens;
    private string $baseUrl = 'https://api.anthropic.com/v1';

    public function __construct()
    {
        // nullの場合も空文字に正規化してgenerate()内で一元チェックする
        $this->apiKey    = config('services.anthropic.api_key') ?? '';
        $this->model     = config('inask.claude.model', 'claude-sonnet-4-6');
        $this->maxTokens = config('inask.claude.max_tokens', 1024);
    }

    // プロンプトをClaude APIに送信してテキストレスポンスを返す
    public function generate(string $prompt): string
    {
        if ($this->apiKey === '') {
            throw new RuntimeException(config('errors.claude.api_key_missing'));
        }

        try {
            $response = Http::withHeaders([
                    // Anthropic APIはBearer認証ではなくx-api-keyヘッダーで認証する
                    'x-api-key'         => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                ])
                ->timeout(30)
                ->retry(3, 1000, function (\Exception $e) {
                    // 接続断（タイムアウト・DNS障害など）は一時的な障害のためリトライする
                    if ($e instanceof ConnectionException) {
                        return true;
                    }
                    if ($e instanceof RequestException) {
                        $status = $e->response->status();
                        // 429（レートリミット）と5xx（サーバーエラー）は回復の見込みがあるためリトライする
                        // 400・401・403・422などの4xx系はリクエスト自体が不正なためリトライしない
                        return $status === 429 || $status >= 500;
                    }
                    return false;
                })
                ->post("{$this->baseUrl}/messages", [
                    'model'      => $this->model,
                    'max_tokens' => $this->maxTokens,
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            // HTTPエラーレスポンスをRequestExceptionとして投げる
            $response->throw();

            // レスポンスのcontent[0].textからテキストを取り出す
            $text = $response->json('content.0.text');

            if (!is_string($text) || $text === '') {
                throw new RuntimeException(config('errors.claude.invalid_response'));
            }

            return $text;
        } catch (RequestException | ConnectionException $e) {
            // HTTPエラーと接続失敗を同じRuntimeExceptionに変換して上位に伝える
            throw new RuntimeException(
                config('errors.claude.request_failed') . ': ' . $e->getMessage(),
                previous: $e,
            );
        }
    }
}
