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
