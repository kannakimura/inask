<?php

namespace Tests\Unit\Clients;

use App\Clients\VoyageClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VoyageClientTest extends TestCase
{
    // embed()が正常なレスポンスからベクトル配列を返す
    public function test_embed_returns_vector_array(): void
    {
        // 1024次元のダミーベクトルを返すモックを設定する
        $dummyVector = array_fill(0, 1024, 0.1);

        Http::fake([
            'api.voyageai.com/*' => Http::response([
                'data' => [
                    ['embedding' => $dummyVector],
                ],
            ], 200),
        ]);

        $client = new VoyageClient();
        $result = $client->embed('テストテキスト');

        $this->assertIsArray($result);
        $this->assertCount(1024, $result);
        $this->assertSame(0.1, $result[0]);
    }

    // APIキーが未設定の場合は例外を投げる
    public function test_embed_throws_exception_when_api_key_is_missing(): void
    {
        config(['services.voyage.api_key' => '']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(config('errors.voyage.api_key_missing'));

        $client = new VoyageClient();
        $client->embed('テスト');
    }

    // APIがエラーを返した場合は例外を投げる
    public function test_embed_throws_exception_on_api_error(): void
    {
        config(['services.voyage.api_key' => 'test-key']);

        Http::fake([
            'api.voyageai.com/*' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(config('errors.voyage.request_failed'));

        $client = new VoyageClient();
        $client->embed('テスト');
    }

    // レスポンスにembeddingが含まれない場合は例外を投げる
    public function test_embed_throws_exception_on_invalid_response(): void
    {
        config(['services.voyage.api_key' => 'test-key']);

        Http::fake([
            'api.voyageai.com/*' => Http::response(['data' => []], 200),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(config('errors.voyage.invalid_response'));

        $client = new VoyageClient();
        $client->embed('テスト');
    }
}
