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

    // ドキュメント一覧を表示する（全認証ユーザーが閲覧可）
    public function index(\Illuminate\Http\Request $request)
    {
        $this->authorize('viewAny', Document::class);

        $keyword = $request->query('keyword', '');

        $query = Document::query()->latest();

        // キーワードが入力されている場合はタイトルまたはチャンク内容で絞り込む
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'ilike', '%' . $keyword . '%')
                  ->orWhereHas('chunks', function ($q2) use ($keyword) {
                      $q2->where('content', 'ilike', '%' . $keyword . '%');
                  });
            });
        }

        $documents = $query->paginate(20)->withQueryString();

        // pending/processingが1件でもある場合はフロントでポーリングを有効にする
        $hasPending = Document::whereIn('status', [
            config('inask.document_status.pending'),
            config('inask.document_status.processing'),
        ])->exists();

        return view('documents.index', compact('documents', 'hasPending', 'keyword'));
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

    // ドキュメント詳細とFAQ一覧を表示する
    public function show(Document $document)
    {
        $this->authorize('view', $document);

        // faqs をeager loadしてN+1を防ぐ
        $document->load('faqs');

        return view('documents.show', compact('document'));
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
