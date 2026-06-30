<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchQueryRequest extends FormRequest
{
    // ログイン済みユーザーのみ許可する
    public function authorize(): bool
    {
        return auth()->check();
    }

    // 検索クエリのバリデーションルール
    public function rules(): array
    {
        return [
            // 最大200文字（長すぎるクエリはembedding品質が低下するため）
            'query' => ['required', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'query.required' => '検索キーワードを入力してください。',
            'query.string'   => '検索キーワードは文字列で入力してください。',
            'query.max'      => '検索キーワードは200文字以内で入力してください。',
        ];
    }
}
