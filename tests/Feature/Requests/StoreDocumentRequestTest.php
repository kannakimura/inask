<?php

namespace Tests\Feature\Requests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreDocumentRequestTest extends TestCase
{
    use RefreshDatabase;

    // ファイルなしで送信するとバリデーションエラーになる
    public function test_file_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('documents.store'), []);

        $response->assertSessionHasErrors('file');
    }

    // 許可されていないMIMEタイプはバリデーションエラーになる
    public function test_invalid_mime_type_is_rejected(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($user)->post(route('documents.store'), ['file' => $file]);

        $response->assertSessionHasErrors('file');
    }

    // PDFファイルはバリデーションを通過する
    public function test_valid_pdf_passes_validation(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post(route('documents.store'), ['file' => $file]);

        // バリデーションエラーがないことを確認（処理自体の成否は問わない）
        $response->assertSessionDoesntHaveErrors('file');
    }

    // テキストファイルはバリデーションを通過する
    public function test_valid_text_file_passes_validation(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

        $response = $this->actingAs($user)->post(route('documents.store'), ['file' => $file]);

        $response->assertSessionDoesntHaveErrors('file');
    }

    // 未認証ユーザーはアップロードできない
    public function test_unauthenticated_user_cannot_upload(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $response = $this->post(route('documents.store'), ['file' => $file]);

        $response->assertRedirect(route('login'));
    }
}
