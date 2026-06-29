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

    // バリデーションエラーメッセージをconfigから取得する
    public function messages(): array
    {
        return [
            'file.required'  => config('errors.file.required'),
            'file.file'      => config('errors.file.file'),
            'file.max'       => config('errors.file.max'),
            'file.mimetypes' => config('errors.file.mimetypes'),
        ];
    }
}
