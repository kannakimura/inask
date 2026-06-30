<?php

namespace Tests\Feature;

use App\Clients\VoyageClient;
use App\Models\Document;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    // DemoSeederが3件のドキュメント・チャンク・FAQを正しく投入する
    public function test_demo_seeder_creates_documents_chunks_and_faqs(): void
    {
        // VoyageClientをモックして1024次元のダミーベクトルを返す
        $dummyVector = array_fill(0, 1024, 0.1);
        $this->mock(VoyageClient::class)
            ->shouldReceive('embedBatch')
            ->andReturnUsing(fn(array $texts) => array_fill(0, count($texts), $dummyVector));

        $this->seed(DemoSeeder::class);

        // 3件のドキュメントが作成されることを確認する
        $this->assertDatabaseCount('documents', 3);

        // 各ドキュメントに5件ずつ、合計15件のチャンクが作成されることを確認する
        $this->assertDatabaseCount('chunks', 15);

        // 各ドキュメントに5件ずつ、合計15件のFAQが作成されることを確認する
        $this->assertDatabaseCount('faqs', 15);

        // ステータスがdoneになっていることを確認する
        $this->assertDatabaseHas('documents', ['title' => '就業規則.txt', 'status' => 'done']);
        $this->assertDatabaseHas('documents', ['title' => '経費精算ガイドライン.txt', 'status' => 'done']);
        $this->assertDatabaseHas('documents', ['title' => 'オンボーディングガイド.txt', 'status' => 'done']);
    }

    // 2回実行しても重複登録しない（冪等性）
    public function test_demo_seeder_is_idempotent(): void
    {
        $dummyVector = array_fill(0, 1024, 0.1);
        $this->mock(VoyageClient::class)
            ->shouldReceive('embedBatch')
            ->andReturnUsing(fn(array $texts) => array_fill(0, count($texts), $dummyVector));

        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        // 2回実行しても3件のままであることを確認する
        $this->assertDatabaseCount('documents', 3);
    }

    // DatabaseSeederを通じてもDemoSeederが呼ばれる
    public function test_database_seeder_calls_demo_seeder(): void
    {
        $dummyVector = array_fill(0, 1024, 0.1);
        $this->mock(VoyageClient::class)
            ->shouldReceive('embedBatch')
            ->andReturnUsing(fn(array $texts) => array_fill(0, count($texts), $dummyVector));

        $this->seed(DatabaseSeeder::class);

        // デモユーザーが作成されることを確認する
        $this->assertDatabaseHas('users', ['email' => 'demo@innask.local']);
        // デモドキュメントが作成されることを確認する
        $this->assertDatabaseCount('documents', 3);
    }
}
