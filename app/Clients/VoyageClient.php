<?php

namespace App\Clients;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class VoyageClient
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://api.voyageai.com/v1';

    public function __construct()
    {
        // nullの場合も空文字に正規化してembed()内で一元チェックする
        $this->apiKey = config('services.voyage.api_key') ?? '';
        $this->model  = config('inask.embedding.model', 'voyage-3');
    }

    // テキストを受け取りembeddingベクトル（float配列）を返す
    public function embed(string $text): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException(config('errors.voyage.api_key_missing'));
        }

        try {
            $response = Http::withToken($this->apiKey)
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
                ->post("{$this->baseUrl}/embeddings", [
                    'model' => $this->model,
                    'input' => [$text],
                ]);

            // HTTPエラーレスポンスをRequestExceptionとして投げる
            $response->throw();

            // レスポンスのdata[0].embeddingからベクトル配列を取り出す
            $embedding = $response->json('data.0.embedding');

            if (!is_array($embedding)) {
                throw new RuntimeException(config('errors.voyage.invalid_response'));
            }

            return $embedding;
        } catch (RequestException | ConnectionException $e) {
            // HTTPエラーと接続失敗を同じRuntimeExceptionに変換して上位に伝える
            // $e->getMessage()で元のエラー内容（ステータスコード・接続エラー詳細）を保持する
            throw new RuntimeException(
                config('errors.voyage.request_failed') . ': ' . $e->getMessage(),
                previous: $e,
            );
        }
    }
}
