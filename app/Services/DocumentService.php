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

    // ドキュメントをDBから削除した後にストレージのファイルを削除する
    public function destroy(Document $document): void
    {
        // 削除前にfile_pathを保持しておく
        $filePath = $document->file_path;

        // DBからドキュメントを先に削除する（chunksとfaqsはcascadeで削除される）
        // DB commit後にファイルを削除することで「ファイルだけ消えてDB残る」を防ぐ（指摘2対応）
        DB::transaction(function () use ($document) {
            $document->delete();
        });

        // DB commit成功後にストレージのファイルを削除する
        Storage::disk('local')->delete($filePath);

        Log::info('ドキュメントを削除しました', [
            'document_id' => $document->id,
            'title'       => $document->title,
        ]);
    }
}
