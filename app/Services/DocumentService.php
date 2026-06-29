<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    // アップロードされたファイルをストレージに保存しDBに登録する
    public function store(UploadedFile $file): Document
    {
        return DB::transaction(function () use ($file) {
            // ストレージへ保存（storage/app/private/documents/）
            $path = $file->store('documents', 'local');

            // DBにドキュメント情報を登録する
            $document = Document::create([
                'title'     => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'status'    => config('inask.document_status.pending'),
            ]);

            Log::info('ドキュメントをアップロードしました', [
                'document_id' => $document->id,
                'title'       => $document->title,
                'mime_type'   => $document->mime_type,
            ]);

            return $document;
        });
    }

    // ドキュメントをストレージとDBから削除する
    public function destroy(Document $document): void
    {
        DB::transaction(function () use ($document) {
            // ストレージからファイルを削除する
            Storage::disk('local')->delete($document->file_path);

            // DBからドキュメントを削除する（chunksとfaqsはcascadeで削除される）
            $document->delete();

            Log::info('ドキュメントを削除しました', [
                'document_id' => $document->id,
                'title'       => $document->title,
            ]);
        });
    }
}
