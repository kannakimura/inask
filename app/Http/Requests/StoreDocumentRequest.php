<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    // ログイン済みユーザーのみ許可する
    public function authorize(): bool
    {
        return auth()->check();
    }

    // アップロードファイルのバリデーションルール
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                // 最大サイズはconfigから取得する（KB単位）
                'max:' . config('inask.max_upload_size_kb', 220),
                'mimetypes:' . implode(',', config('inask.supported_mime_types', [])),
            ],
        ];
    }

    // バリデーションエラーメッセージをconfigから取得する（サイズ上限は動的に組み立てる）
    public function messages(): array
    {
        $maxMb = config('inask.max_upload_size_kb', 220) / 1024;

        return [
            'file.required'  => config('errors.file.required'),
            'file.file'      => config('errors.file.file'),
            'file.max'       => "ファイルサイズは{$maxMb}MB以内にしてください。",
            'file.mimetypes' => config('errors.file.mimetypes'),
        ];
    }
}
