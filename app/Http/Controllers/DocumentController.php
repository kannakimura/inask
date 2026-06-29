<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentController extends Controller
{
    // TODO(Phase 2-3): アップロード処理・バリデーション・Serviceの呼び出しを実装する

    // ドキュメント一覧を表示する
    public function index()
    {
        // TODO(Phase 2-7): ドキュメント一覧の取得・表示を実装する
        abort(501);
    }

    // アップロードフォームを表示する
    public function create()
    {
        // TODO(Phase 2-5): アップロードフォームのViewを返す
        abort(501);
    }

    // アップロードされたファイルを保存する
    public function store(Request $request)
    {
        // TODO(Phase 2-4): バリデーション・ストレージ保存・DB登録を実装する
        abort(501);
    }

    // ドキュメント詳細（FAQ一覧）を表示する
    public function show(string $id)
    {
        // TODO(Phase 2-7): ドキュメント詳細・FAQ一覧の表示を実装する
        abort(501);
    }

    // ドキュメントを削除する
    public function destroy(string $id)
    {
        // TODO(Phase 2-7): ドキュメント削除処理を実装する
        abort(501);
    }
}
