<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    // 認証済みユーザー（非admin）はダッシュボードを表示できる
    public function test_authenticated_user_can_view_dashboard(): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
    }

    // 非adminユーザーにはアップロードフォームが表示されない
    public function test_non_admin_cannot_see_upload_form(): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('ドキュメントをアップロード');
    }

    // adminユーザーにはアップロードフォームが表示される
    public function test_admin_can_see_upload_form(): void
    {
        $this->withoutVite();
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

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
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->withSession(['success' => 'ドキュメントをアップロードしました。'])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('ドキュメントをアップロードしました。');
    }

    // adminはファイルをアップロードできる
    public function test_admin_can_upload_file(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['is_admin' => true]);
        $file  = UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf');

        $response = $this->actingAs($admin)->post(route('documents.store'), ['file' => $file]);

        $response->assertRedirect(route('documents.index'));
        $response->assertSessionHas('success');
    }

    // 非adminはファイルをアップロードできない
    public function test_non_admin_cannot_upload_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['is_admin' => false]);
        $file = UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post(route('documents.store'), ['file' => $file]);

        $response->assertStatus(403);
    }

    // ドキュメントが存在する場合は一覧に表示される
    public function test_documents_are_listed_on_dashboard(): void
    {
        $this->withoutVite();
        $user     = User::factory()->create(['is_admin' => false]);
        $document = Document::factory()->create(['title' => 'テスト仕様書.pdf']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('テスト仕様書.pdf');
    }

    // ドキュメントが0件の場合は空メッセージが表示される
    public function test_empty_message_is_shown_when_no_documents(): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('アップロードされたドキュメントはありません。');
    }

    // adminユーザーには削除ボタンが表示される
    public function test_admin_sees_delete_button(): void
    {
        $this->withoutVite();
        $admin    = User::factory()->create(['is_admin' => true]);
        $document = Document::factory()->create();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('削除');
    }

    // 非adminユーザーには削除ボタンが表示されない
    public function test_non_admin_does_not_see_delete_button(): void
    {
        $this->withoutVite();
        $user     = User::factory()->create(['is_admin' => false]);
        $document = Document::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('削除');
    }
}
