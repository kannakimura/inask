<?php

namespace Tests\Unit\Clients;

use App\Clients\VoyageClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VoyageClientTest extends TestCase
{
    // embedBatch()が正常なレスポンスからベクトル配列の配列を返す
    public function test_embed_batch_returns_vector_arrays(): void
    {
        config(['services.voyage.api_key' => 'test-key']);

        $dummyVector1 = array_fill(0, 1024, 0.1);
        $dummyVector2 = array_fill(0, 1024, 0.2);

        // Voyage APIはdata[].indexでソート前の順序で返す場合があるため逆順でモックする
        Http::fake([
            'api.voyageai.com/*' => Http::response([
                'data' => [
                    ['index' => 1, 'embedding' => $dummyVector2],
                    ['index' => 0, 'embedding' => $dummyVector1],
                ],
            ], 200),
        ]);

        $client = new VoyageClient();
        $result = $client->embedBatch(['テキスト1', 'テキスト2']);

        // 入力順通りに並んでいることを確認する
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertCount(1024, $result[0]);
        $this->assertSame(0.1, $result[0][0]);
        $this->assertSame(0.2, $result[1][0]);
    }

    // APIキーが未設定の場合は例外を投げる
    public function test_embed_batch_throws_exception_when_api_key_is_missing(): void
    {
        config(['services.voyage.api_key' => '']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(config('errors.voyage.api_key_missing'));

        $client = new VoyageClient();
        $client->embedBatch(['テスト']);
    }

    // APIがエラーを返した場合は例外を投げる
    public function test_embed_batch_throws_exception_on_api_error(): void
    {
        config(['services.voyage.api_key' => 'test-key']);

        // リトライが走らないよう常に401を返すシーケンスを設定する
        Http::fake([
            'api.voyageai.com/*' => Http::sequence()
                ->push(['error' => 'Unauthorized'], 401)
                ->push(['error' => 'Unauthorized'], 401)
                ->push(['error' => 'Unauthorized'], 401),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(config('errors.voyage.request_failed'));

        $client = new VoyageClient();
        $client->embedBatch(['テスト']);
    }

    // 接続失敗（タイムアウト・DNS障害など）の場合も同じ例外経路になる
    public function test_embed_batch_throws_exception_on_connection_failure(): void
    {
        config(['services.voyage.api_key' => 'test-key']);

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(config('errors.voyage.request_failed'));

        $client = new VoyageClient();
        $client->embedBatch(['テスト']);
    }

    // レスポンスのdata件数が入力と一致しない場合は例外を投げる
    public function test_embed_batch_throws_exception_on_invalid_response(): void
    {
        config(['services.voyage.api_key' => 'test-key']);

        // 2件送ったが1件しか返ってこない不正レスポンスをモックする
        Http::fake([
            'api.voyageai.com/*' => Http::response([
                'data' => [
                    ['index' => 0, 'embedding' => array_fill(0, 1024, 0.1)],
                ],
            ], 200),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(config('errors.voyage.invalid_response'));

        $client = new VoyageClient();
        $client->embedBatch(['テキスト1', 'テキスト2']);
    }

    // indexが重複している場合は不正レスポンスとして例外を投げる
    public function test_embed_batch_throws_exception_on_duplicate_index(): void
    {
        config(['services.voyage.api_key' => 'test-key']);

        $vector = array_fill(0, 1024, 0.1);
        Http::fake([
            'api.voyageai.com/*' => Http::response([
                'data' => [
                    ['index' => 0, 'embedding' => $vector],
                    ['index' => 0, 'embedding' => $vector], // indexが重複している
                ],
            ], 200),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(config('errors.voyage.invalid_response'));

        $client = new VoyageClient();
        $client->embedBatch(['テキスト1', 'テキスト2']);
    }

    // indexに欠番がある場合は不正レスポンスとして例外を投げる
    public function test_embed_batch_throws_exception_on_missing_index(): void
    {
        config(['services.voyage.api_key' => 'test-key']);

        $vector = array_fill(0, 1024, 0.1);
        Http::fake([
            'api.voyageai.com/*' => Http::response([
                'data' => [
                    ['index' => 0, 'embedding' => $vector],
                    ['index' => 2, 'embedding' => $vector], // index=1が欠番
                ],
            ], 200),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(config('errors.voyage.invalid_response'));

        $client = new VoyageClient();
        $client->embedBatch(['テキスト1', 'テキスト2']);
    }
}
