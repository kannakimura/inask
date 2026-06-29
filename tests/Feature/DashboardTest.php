<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    // 認証済みユーザーはダッシュボードを表示できる
    public function test_authenticated_user_can_view_dashboard(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('ドキュメントをアップロード');
    }

    // 未認証ユーザーはダッシュボードにアクセスできない
    public function test_unauthenticated_user_is_redirected(): void
    {
        $this->withoutVite();
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    // アップロード成功後にフラッシュメッセージが表示される
    public function test_success_flash_message_is_displayed(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['success' => 'ドキュメントをアップロードしました。'])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('ドキュメントをアップロードしました。');
    }

    // アップロードフォームからファイルを送信できる
    public function test_user_can_upload_file_from_dashboard(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post(route('documents.store'), ['file' => $file]);

        // アップロード成功後にdashboardへリダイレクトする
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');
    }
}
