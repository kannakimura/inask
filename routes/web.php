<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;

// トップページ
Route::get('/', function () {
    return view('welcome');
});

// ドキュメント管理ルート
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
