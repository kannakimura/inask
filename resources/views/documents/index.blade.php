<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ config('messages.documents.page_title') }}
            </h2>
            {{-- pending/processing中はポーリング中インジケーターを表示する --}}
            @if ($hasPending)
                <span
                    class="inline-flex items-center gap-1.5 text-xs text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-full px-3 py-1"
                    data-auto-reload="true"
                >
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-yellow-500"></span>
                    </span>
                    {{ config('messages.documents.processing_notice') }}
                </span>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ドキュメントアップロードフォーム（adminのみ表示） --}}
            @if (auth()->user()?->is_admin)
            <div id="upload-form" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ config('messages.documents.upload_section_title') }}</h3>

                    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label for="file" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ config('messages.documents.file_label') }}
                                @php
                                    $maxKb = config('inask.max_upload_size_kb', 220);
                                    $maxLabel = $maxKb >= 1024
                                        ? round($maxKb / 1024, 1) . 'MB'
                                        : $maxKb . 'KB';
                                @endphp
                                <span class="text-gray-400 font-normal">（PDF・テキスト・Markdown、最大{{ $maxLabel }}）</span>
                            </label>
                            <input
                                type="file"
                                id="file"
                                name="file"
                                accept=".pdf,.txt,.md"
                                class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-2"
                            >
                            @error('file')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                {{ config('messages.documents.upload_button') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            {{-- キーワード絞り込みフォーム --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4">
                    <form method="GET" action="{{ route('documents.index') }}" class="flex gap-3">
                        <input
                            type="text"
                            name="keyword"
                            value="{{ $keyword }}"
                            placeholder="{{ config('messages.documents.keyword_placeholder') }}"
                            class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 transition ease-in-out duration-150"
                        >
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            {{ config('messages.documents.keyword_button') }}
                        </button>
                        @if ($keyword !== '')
                            <a
                                href="{{ route('documents.index') }}"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition ease-in-out duration-150"
                            >
                                {{ config('messages.documents.keyword_clear') }}
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            {{-- ドキュメント一覧 --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="divide-y divide-gray-100">

                    @if ($documents->isEmpty())
                        {{-- 0件の空状態 --}}
                        <div class="flex flex-col items-center justify-center py-16 text-center px-6">
                            <svg class="w-12 h-12 text-gray-300 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            @if ($keyword !== '')
                                <p class="text-gray-500 text-sm">{{ config('messages.documents.no_results') }}</p>
                            @else
                                <p class="text-gray-500 text-sm font-medium">{{ config('errors.dashboard.no_documents') }}</p>
                                <p class="text-gray-400 text-xs mt-1">{{ config('errors.dashboard.no_documents_hint') }}</p>
                                @if (auth()->user()?->is_admin)
                                    <a href="#upload-form" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 transition ease-in-out duration-150">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                        </svg>
                                        {{ config('messages.documents.upload_cta_button') }}
                                    </a>
                                @endif
                            @endif
                        </div>
                    @else
                        @php
                            $isAdmin = auth()->user()?->is_admin ?? false;
                            $statusLabels       = config('inask.document_status_labels', []);
                            $statusBadgeClasses = config('inask.document_status_badge_classes', []);
                        @endphp

                        @foreach ($documents as $document)
                            @php
                                $statusLabel = $statusLabels[$document->status] ?? $document->status;
                                $badgeClass  = $statusBadgeClasses[$document->status] ?? 'bg-gray-100 text-gray-800';
                            @endphp

                            <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition">
                                {{-- クリックでドキュメント詳細へ --}}
                                <a href="{{ route('documents.show', $document) }}" class="flex items-center gap-4 min-w-0 flex-1">
                                    <svg class="w-8 h-8 text-indigo-300 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $document->title }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $document->created_at->format('Y/m/d H:i') }}</p>
                                    </div>
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium shrink-0 {{ $badgeClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </a>

                                {{-- 削除ボタン（adminのみ） --}}
                                @if ($isAdmin)
                                    <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('削除しますか？')" class="ml-6 shrink-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium">
                                            {{ config('messages.documents.delete_button') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach

                        {{-- ページネーション --}}
                        @if ($documents->hasPages())
                            <div class="px-6 py-4">
                                {{ $documents->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- pending/processing中は3秒おきに自動リロードする --}}
    @if ($hasPending)
        <script>
            setTimeout(function () {
                location.reload();
            }, 3000);
        </script>
    @endif
</x-app-layout>
