<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // pgvector拡張がなければ作成する（テスト用DBでも有効になるよう冪等で実行）
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('document_title');
            $table->text('content');
            $table->integer('chunk_index');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE chunks ADD COLUMN embedding vector(1024)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chunks');
    }
};
