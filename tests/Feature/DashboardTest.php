<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
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
        Queue::fake(); // ProcessDocumentJobをインライン実行させない
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
        Queue::fake(); // 403前にJobが実行されないよう念のためfakeする
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

    // pending/processingのドキュメントがある場合はdata-auto-reload属性とJSが出力される
    public function test_auto_reload_is_present_when_pending_document_exists(): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['is_admin' => false]);
        Document::factory()->create(['status' => config('inask.document_status.pending')]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        // ポーリング中インジケーターが表示されることを確認する
        $response->assertSee('data-auto-reload="true"', false);
        // 自動リロードのJSが出力されることを確認する
        $response->assertSee('location.reload()', false);
    }

    // processingのドキュメントがある場合も自動リロードが有効になる
    public function test_auto_reload_is_present_when_processing_document_exists(): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['is_admin' => false]);
        Document::factory()->create(['status' => config('inask.document_status.processing')]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('data-auto-reload="true"', false);
    }

    // done/failedのみの場合は自動リロードが無効になる
    public function test_auto_reload_is_absent_when_all_documents_are_done(): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['is_admin' => false]);
        Document::factory()->create(['status' => config('inask.document_status.done')]);
        Document::factory()->create(['status' => config('inask.document_status.failed')]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('data-auto-reload="true"', false);
        $response->assertDontSee('location.reload()', false);
    }

    // doneかつFAQがある場合はアコーディオン形式でFAQが表示される
    public function test_faqs_are_shown_as_accordion_when_document_is_done(): void
    {
        $this->withoutVite();
        $user     = User::factory()->create(['is_admin' => false]);
        $document = Document::factory()->create(['status' => config('inask.document_status.done')]);
        Faq::factory()->create([
            'document_id' => $document->id,
            'question'    => 'テスト質問ですか？',
            'answer'      => 'テスト回答です。',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        // アコーディオン構造（details/summary）とQ&A内容が出力されることを確認する
        $response->assertSee('data-faq-section', false);
        $response->assertSee('テスト質問ですか？');
        $response->assertSee('テスト回答です。');
    }

    // doneだがFAQが0件の場合は未生成メッセージが表示される
    public function test_faq_not_generated_message_shown_when_done_but_no_faqs(): void
    {
        $this->withoutVite();
        $user     = User::factory()->create(['is_admin' => false]);
        Document::factory()->create(['status' => config('inask.document_status.done')]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('FAQはまだ生成されていません。');
    }

    // failedステータスのドキュメントはFAQ生成失敗メッセージが表示される
    public function test_faq_failed_message_shown_when_document_failed(): void
    {
        $this->withoutVite();
        $user     = User::factory()->create(['is_admin' => false]);
        Document::factory()->create(['status' => config('inask.document_status.failed')]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('処理に失敗したため、FAQを生成できませんでした。');
    }

    // pending/processingのドキュメントはFAQ待機メッセージが表示される
    public function test_faq_pending_message_shown_when_document_is_processing(): void
    {
        $this->withoutVite();
        $user     = User::factory()->create(['is_admin' => false]);
        Document::factory()->create(['status' => config('inask.document_status.processing')]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('ベクトル化処理が完了するとFAQが表示されます。');
    }
}
