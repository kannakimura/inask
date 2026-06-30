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
                        <div class="flex gap-3">
                            <input
                                type="text"
                                name="query"
                                value="{{ old('query', $query ?? '') }}"
                                placeholder="質問を入力してください（例：有給休暇の申請方法は？）"
                                class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                autofocus
                            >
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
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

            {{-- 検索結果0件（関連ドキュメントなし） --}}
            @isset($searchError)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg p-4 text-sm">
                    {{ $searchError }}
                </div>
            @endisset

            {{-- RAG回答 --}}
            @isset($result)
                {{-- 回答本文 --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-gray-900 mb-3">回答</h3>
                        <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ $result->answer }}</div>
                    </div>
                </div>

                {{-- 出典チャンク --}}
                @if (!empty($result->sources))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-base font-semibold text-gray-900 mb-3">参照ドキュメント</h3>
                            <div class="space-y-2">
                                @foreach ($result->sources as $i => $source)
                                    <details class="group border border-gray-100 rounded">
                                        <summary class="flex items-center justify-between px-3 py-2 cursor-pointer select-none list-none text-sm text-gray-600 hover:bg-gray-50 rounded">
                                            <span>
                                                <span class="font-medium text-gray-500 mr-2">出典 {{ $i + 1 }}</span>
                                                {{ $source->documentTitle }}
                                            </span>
                                            <svg class="w-4 h-4 text-gray-400 shrink-0 ml-2 transition-transform group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </summary>
                                        <div class="px-3 py-2 text-xs text-gray-500 border-t border-gray-100 bg-gray-50 whitespace-pre-wrap">{{ $source->content }}</div>
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
