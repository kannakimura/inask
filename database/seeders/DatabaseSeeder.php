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
        // デモ用ユーザーを作成する（local環境のみis_admin=true）
        User::updateOrCreate(
            ['email' => 'demo@innask.local'],
            [
                'name'     => 'Demo User',
                'password' => bcrypt('password'),
                'is_admin' => app()->environment('local'),
            ]
        );

        // デモ用ドキュメント・チャンク・FAQを投入する
        $this->call(DemoSeeder::class);
    }
}
