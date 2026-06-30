<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ダッシュボードはドキュメント一覧を兼ねるためControllerに委譲する
Route::get('/dashboard', [DocumentController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 検索ルート（認証必須）
    Route::prefix('search')->name('search.')->group(function () {
        // 検索フォーム表示
        Route::get('/', [SearchController::class, 'index'])->name('index');
        // 検索実行
        Route::post('/', [SearchController::class, 'search'])->name('query');
    });

    // ドキュメント管理ルート（認証必須）
    Route::prefix('documents')->name('documents.')->group(function () {
        // ドキュメント一覧
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        // アップロードフォーム表示
        Route::get('/create', [DocumentController::class, 'create'])->name('create');
        // アップロード処理
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        // ドキュメント詳細（FAQ一覧）
        Route::get('/{document}', [DocumentController::class, 'show'])->name('show');
        // ドキュメント削除
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
    });
});

require __DIR__.'/auth.php';
