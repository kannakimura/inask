<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    // adminユーザーのみドキュメントを削除できる
    public function delete(User $user, Document $document): bool
    {
        return $user->is_admin;
    }
}
