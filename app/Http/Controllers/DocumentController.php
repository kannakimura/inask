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

    // ダッシュボード兼ドキュメント一覧を表示する（全認証ユーザーが閲覧可）
    public function index()
    {
        $this->authorize('viewAny', Document::class);

        // faqs をeager loadしてN+1を防ぐ
        $documents = Document::with('faqs')->latest()->paginate(20);

        // pending/processingが1件でもある場合はフロントでポーリングを有効にする
        $hasPending = Document::whereIn('status', [
            config('inask.document_status.pending'),
            config('inask.document_status.processing'),
        ])->exists();

        return view('dashboard', compact('documents', 'hasPending'));
    }

    // アップロードフォームを表示する（ダッシュボード埋め込みのため未使用）
    public function create()
    {
        abort(403);
    }

    // アップロードされたファイルを保存する（adminのみ）
    public function store(StoreDocumentRequest $request)
    {
        // アップロードはadmin専用機能（DocumentPolicy::createで判定）
        $this->authorize('create', Document::class);

        // バリデーション済みファイルをServiceに渡して保存する
        $document = $this->documentService->store($request->file('file'));

        return redirect()
            ->route('documents.index')
            ->with('success', config('errors.document.upload_success'));
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
            ->with('success', config('errors.document.delete_success'));
    }
}
