<?php

namespace Tests\Unit\Policies;

use App\Models\Document;
use App\Models\User;
use App\Policies\DocumentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private DocumentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new DocumentPolicy();
    }

    // 全認証ユーザーはドキュメント一覧を閲覧できる
    public function test_any_authenticated_user_can_view_documents(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->assertTrue($this->policy->viewAny($user));
    }

    // adminユーザーはドキュメントをアップロードできる
    public function test_admin_can_create_document(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($this->policy->create($admin));
    }

    // 非adminユーザーはドキュメントをアップロードできない
    public function test_non_admin_cannot_create_document(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->assertFalse($this->policy->create($user));
    }

    // adminユーザーはドキュメントを削除できる
    public function test_admin_can_delete_document(): void
    {
        $admin    = User::factory()->create(['is_admin' => true]);
        $document = Document::factory()->create();

        $this->assertTrue($this->policy->delete($admin, $document));
    }

    // 非adminユーザーはドキュメントを削除できない
    public function test_non_admin_cannot_delete_document(): void
    {
        $user     = User::factory()->create(['is_admin' => false]);
        $document = Document::factory()->create();

        $this->assertFalse($this->policy->delete($user, $document));
    }
}
