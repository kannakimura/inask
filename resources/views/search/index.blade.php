<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            社内ドキュメント検索
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 検索フォーム --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('search.query') }}">
                        @csrf
                        <label for="query" class="block text-sm font-medium text-gray-700 mb-2">
                            質問を入力してください
                        </label>
                        <div class="flex gap-3">
                            <input
                                type="text"
                                id="query"
                                name="query"
                                value="{{ old('query', $query ?? '') }}"
                                placeholder="例：有給休暇の申請方法は？"
                                maxlength="200"
                                class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                autofocus
                            >
                            <button
                                type="submit"
                                class="inline-flex items-center gap-1.5 px-5 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                                検索
                            </button>
                        </div>
                        {{-- バリデーションエラー --}}
                        @error('query')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </form>
                </div>
            </div>

            {{-- 初期状態（まだ何も検索していない） --}}
            @if (!isset($result) && !isset($searchError) && !$errors->any())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8 flex flex-col items-center text-center">
                        <svg class="w-12 h-12 text-indigo-200 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                        </svg>
                        <p class="text-gray-600 text-sm font-medium">社内ドキュメントに質問してみましょう</p>
                        <p class="text-gray-400 text-xs mt-1 mb-5">登録されているドキュメントをもとに、AIが回答を生成します</p>
                        {{-- 検索例 --}}
                        <div class="flex flex-wrap justify-center gap-2">
                            @foreach (['有給休暇の申請方法は？', '経費精算の締め日はいつ？', '入社初日の持ち物は？'] as $example)
                                <button
                                    type="button"
                                    onclick="document.getElementById('query').value = '{{ $example }}'; document.getElementById('query').focus();"
                                    class="px-3 py-1.5 rounded-full border border-gray-200 text-xs text-gray-500 hover:border-indigo-400 hover:text-indigo-600 hover:bg-indigo-50 transition"
                                >
                                    {{ $example }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- 検索結果0件（関連ドキュメントなし） --}}
            @isset($searchError)
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex items-start gap-3">
                    <svg class="w-5 h-5 text-yellow-500 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <p class="text-sm text-yellow-800">{{ $searchError }}</p>
                </div>
            @endisset

            {{-- RAG回答 --}}
            @isset($result)
                {{-- 回答本文 --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <svg class="w-5 h-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                            </svg>
                            <h3 class="text-base font-semibold text-gray-900">回答</h3>
                        </div>
                        {{-- whitespace-pre-wrapでClaude回答の改行・箇条書きをそのまま表示する --}}
                        <div class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $result->answer }}</div>
                    </div>
                </div>

                {{-- 出典チャンク --}}
                @if (!empty($result->sources))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <h3 class="text-sm font-semibold text-gray-600">参照ドキュメント（{{ count($result->sources) }}件）</h3>
                            </div>
                            <div class="space-y-2">
                                @foreach ($result->sources as $i => $source)
                                    <details class="group border border-gray-100 rounded-lg">
                                        <summary class="flex items-center justify-between px-4 py-3 cursor-pointer select-none list-none hover:bg-gray-50 rounded-lg">
                                            <div class="flex items-center gap-2 min-w-0">
                                                {{-- 出典番号バッジ --}}
                                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold shrink-0">{{ $i + 1 }}</span>
                                                <span class="text-sm text-gray-700 truncate">{{ $source->documentTitle }}</span>
                                            </div>
                                            <svg class="w-4 h-4 text-gray-400 shrink-0 ml-3 transition-transform group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </summary>
                                        <div class="px-4 py-3 text-xs text-gray-500 border-t border-gray-100 bg-gray-50 whitespace-pre-wrap leading-relaxed rounded-b-lg">{{ $source->content }}</div>
                                    </details>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            @endisset

        </div>
    </div>
</x-app-layout>
