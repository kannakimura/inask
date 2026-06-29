<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DocumentService
{
    // アップロードされたファイルをストレージに保存しDBに登録する
    public function store(UploadedFile $file): Document
    {
        // ストレージへ保存（storage/app/private/documents/）
        $path = $file->store('documents', 'local');

        // store()はfalseを返す可能性があるため検査する（指摘5対応）
        if ($path === false) {
            throw new RuntimeException('ファイルの保存に失敗しました。');
        }

        try {
            // DBへの登録をトランザクションで行う
            $document = DB::transaction(function () use ($file, $path) {
                return Document::create([
                    'title'     => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'status'    => config('inask.document_status.pending'),
                ]);
            });
        } catch (\Throwable $e) {
            // DB登録失敗時はアップロード済みファイルを削除してcleanupする（指摘3対応）
            Storage::disk('local')->delete($path);
            Log::error('ドキュメントのDB登録に失敗しました。ファイルを削除しました。', [
                'path'  => $path,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        Log::info('ドキュメントをアップロードしました', [
            'document_id' => $document->id,
            'title'       => $document->title,
            'mime_type'   => $document->mime_type,
        ]);

        return $document;
    }

    // ドキュメントを削除する（DB削除成功後にファイルを削除する）
    public function destroy(Document $document): void
    {
        $filePath    = $document->file_path;
        $documentId  = $document->id;

        // DBからドキュメントを先に削除する（chunksとfaqsはcascadeで削除）
        // DB削除成功後にファイルを削除することで、ファイル削除失敗時も
        // アプリからは見えない状態になりリトライ不要になる。
        // ファイルがストレージに孤立する可能性はあるが実害はない（設計上のトレードオフ）
        DB::transaction(function () use ($document) {
            $document->delete();
        });

        // DB削除確定後にストレージのファイルを削除する（トランザクション外）
        // 失敗してもDBからは追跡不能なため専用チャンネルにログを残す
        $fileDeleted = Storage::disk('local')->delete($filePath);

        if (!$fileDeleted) {
            Log::channel('file_deletion')->warning('ファイル削除に失敗しました（孤立ファイル）', [
                'document_id' => $documentId,
                'file_path'   => $filePath,
            ]);
        }

        Log::info('ドキュメントを削除しました', [
            'document_id' => $documentId,
        ]);
    }
}
