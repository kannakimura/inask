<?php

namespace App\Clients;

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
        $this->apiKey = config('services.voyage.api_key', '');
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
                ->post("{$this->baseUrl}/embeddings", [
                    'model' => $this->model,
                    'input' => [$text],
                ]);

            $response->throw();

            // レスポンスからembeddingベクトルを取り出す
            $embedding = $response->json('data.0.embedding');

            if (!is_array($embedding)) {
                throw new RuntimeException(config('errors.voyage.invalid_response'));
            }

            return $embedding;
        } catch (RequestException $e) {
            throw new RuntimeException(
                config('errors.voyage.request_failed') . ': ' . $e->getMessage(),
                previous: $e,
            );
        }
    }
}
