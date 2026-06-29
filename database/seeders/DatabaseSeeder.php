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
        User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@innask.local',
            'password' => bcrypt('password'),
        ]);
    }
}
