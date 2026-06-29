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
                'max:10240', // 最大10MB
                'mimetypes:' . implode(',', config('inask.supported_mime_types', [])),
            ],
        ];
    }

    // バリデーションエラーメッセージの日本語化
    public function messages(): array
    {
        return [
            'file.required'  => 'ファイルを選択してください。',
            'file.file'      => '有効なファイルをアップロードしてください。',
            'file.max'       => 'ファイルサイズは10MB以内にしてください。',
            'file.mimetypes' => 'PDF・テキスト・Markdownファイルのみアップロードできます。',
        ];
    }
}
