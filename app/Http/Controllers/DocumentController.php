<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Services\DocumentService;

class DocumentController extends Controller
{
    // DocumentServiceをDIで受け取る
    public function __construct(private DocumentService $documentService)
    {
    }

    // ダッシュボード兼ドキュメント一覧を表示する
    public function index()
    {
        // 全ドキュメントを新しい順に取得してダッシュボードに渡す
        $documents = Document::latest()->get();

        return view('dashboard', compact('documents'));
    }

    // アップロードフォームを表示する（ダッシュボード埋め込みのため未使用）
    public function create()
    {
        abort(403);
    }

    // アップロードされたファイルを保存する
    public function store(StoreDocumentRequest $request)
    {
        // バリデーション済みファイルをServiceに渡して保存する
        $document = $this->documentService->store($request->file('file'));

        return redirect()
            ->route('documents.index')
            ->with('success', 'ドキュメントをアップロードしました。');
    }

    // ドキュメント詳細（FAQ一覧）を表示する
    public function show(Document $document)
    {
        // TODO(Phase 2-7): ドキュメント詳細・FAQ一覧の表示を実装する
        abort(403);
    }

    // ドキュメントを削除する
    public function destroy(Document $document)
    {
        // adminユーザーのみ削除を許可する（DocumentPolicy::deleteで判定）
        $this->authorize('delete', $document);

        // Serviceに削除処理を委譲する
        $this->documentService->destroy($document);

        return redirect()
            ->route('documents.index')
            ->with('success', 'ドキュメントを削除しました。');
    }
}
