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

    // adminユーザーはドキュメント一覧を閲覧できる
    public function test_admin_can_view_any_document(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($this->policy->viewAny($admin));
    }

    // 非adminユーザーはドキュメント一覧を閲覧できない
    public function test_non_admin_cannot_view_any_document(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->assertFalse($this->policy->viewAny($user));
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
