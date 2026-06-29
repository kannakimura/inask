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
    public function store(StoreDocumentRequest $request)
    {
        // バリデーション済みファイルをServiceに渡して保存する
        $document = $this->documentService->store($request->file('file'));

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'ドキュメントをアップロードしました。');
    }

    // ドキュメント詳細（FAQ一覧）を表示する
    public function show(Document $document)
    {
        // TODO(Phase 2-7): ドキュメント詳細・FAQ一覧の表示を実装する
        abort(501);
    }

    // ドキュメントを削除する
    public function destroy(Document $document)
    {
        // Serviceに削除処理を委譲する
        $this->documentService->destroy($document);

        return redirect()
            ->route('documents.index')
            ->with('success', 'ドキュメントを削除しました。');
    }
}
