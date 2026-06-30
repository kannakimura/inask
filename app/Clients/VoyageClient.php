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
        // nullの場合も空文字に正規化してembedBatch()内で一元チェックする
        $this->apiKey = config('services.voyage.api_key') ?? '';
        $this->model  = config('inask.embedding.model', 'voyage-3');
    }

    // テキスト配列を一括でembeddingし、入力順通りのベクトル配列を返す
    // 1リクエストで全チャンクをAPIに送ることで直列呼び出しによるタイムアウトを防ぐ
    public function embedBatch(array $texts): array
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
                    'input' => $texts,
                ]);

            // HTTPエラーレスポンスをRequestExceptionとして投げる
            $response->throw();

            // レスポンスのdataからembedding配列を取り出す
            $data = $response->json('data');

            if (!is_array($data) || count($data) !== count($texts)) {
                throw new RuntimeException(config('errors.voyage.invalid_response'));
            }

            // data[].indexでソートして入力テキストの順序と一致させる
            // （Voyage APIはindex順に返すとは保証していないため）
            usort($data, fn($a, $b) => $a['index'] <=> $b['index']);

            // indexが0..n-1を過不足なく持つことを検証する（欠番・重複対策）
            // 外部APIの壊れたレスポンスで異なるチャンクのembeddingを誤った位置に保存するのを防ぐ
            $actualIndexes   = array_column($data, 'index');
            $expectedIndexes = range(0, count($texts) - 1);
            if ($actualIndexes !== $expectedIndexes) {
                throw new RuntimeException(config('errors.voyage.invalid_response'));
            }

            // 各embeddingが配列であることを確認する
            foreach ($data as $item) {
                if (!is_array($item['embedding'] ?? null)) {
                    throw new RuntimeException(config('errors.voyage.invalid_response'));
                }
            }

            return array_column($data, 'embedding');
        } catch (RequestException | ConnectionException $e) {
            // HTTPエラーと接続失敗を同じRuntimeExceptionに変換して上位に伝える
            throw new RuntimeException(
                config('errors.voyage.request_failed') . ': ' . $e->getMessage(),
                previous: $e,
            );
        }
    }
}
