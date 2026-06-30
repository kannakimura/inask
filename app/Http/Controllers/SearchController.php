<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchQueryRequest;
use App\Services\AnswerGeneratorService;
use App\Services\SearchService;

class SearchController extends Controller
{
    public function __construct(
        private SearchService $searchService,
        private AnswerGeneratorService $answerGeneratorService,
    ) {
    }

    // 検索フォームを表示する
    public function index()
    {
        return view('search.index');
    }

    // クエリを受け取りRAG回答を生成して結果ビューに渡す
    public function search(SearchQueryRequest $request)
    {
        $query = $request->validated()['query'];

        try {
            // クエリに近いChunkをベクトル検索で取得する
            $sources = $this->searchService->search($query);

            // 検索結果が0件の場合はAnswerGeneratorServiceがInvalidArgumentExceptionを投げる
            $result = $this->answerGeneratorService->generate($query, $sources);

            return view('search.index', compact('query', 'result'));
        } catch (\InvalidArgumentException $e) {
            // 検索結果0件は正常系の一部としてエラーメッセージをビューに渡す
            return view('search.index', [
                'query'        => $query,
                'searchError'  => $e->getMessage(),
            ]);
        }
    }
}
