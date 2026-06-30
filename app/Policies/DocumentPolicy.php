<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    // 認証ユーザーは全員ドキュメント一覧を閲覧できる
    public function viewAny(User $user): bool
    {
        return true;
    }

    // adminユーザーのみドキュメントをアップロードできる
    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    // adminユーザーのみドキュメントを削除できる
    public function delete(User $user, Document $document): bool
    {
        return $user->is_admin;
    }
}
