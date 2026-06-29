<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // デモ用ユーザーを1件作成する
        // デモ用adminユーザーを作成する（既存の場合はis_adminをtrueに更新する）
        User::updateOrCreate(
            ['email' => 'demo@innask.local'],
            [
                'name'     => 'Demo User',
                'password' => bcrypt('password'),
                'is_admin' => true,
            ]
        );
    }
}
