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
            'query.required' => config('errors.search.query_required'),
            'query.string'   => config('errors.search.query_string'),
            'query.max'      => config('errors.search.query_max'),
        ];
    }
}
