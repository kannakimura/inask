<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    // adminユーザーのみドキュメント一覧を閲覧できる
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    // adminユーザーのみドキュメントを削除できる
    public function delete(User $user, Document $document): bool
    {
        return $user->is_admin;
    }
}
