<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ config('messages.dashboard.page_title') }}
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
                    {{ config('messages.dashboard.processing_notice') }}
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
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ config('messages.dashboard.upload_section_title') }}</h3>

                    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- ファイル選択 --}}
                        <div class="mb-4">
                            <label for="file" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ config('messages.dashboard.file_label') }}
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
                            {{-- バリデーションエラー --}}
                            @error('file')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 送信ボタン --}}
                        <div>
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                {{ config('messages.dashboard.upload_button') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            {{-- ドキュメント一覧 --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ config('messages.dashboard.list_section_title') }}</h3>

                    @if ($documents->isEmpty())
                        {{-- ドキュメント0件の空状態 --}}
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <svg class="w-12 h-12 text-gray-300 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <p class="text-gray-500 text-sm font-medium">{{ config('errors.dashboard.no_documents') }}</p>
                            <p class="text-gray-400 text-xs mt-1">{{ config('errors.dashboard.no_documents_hint') }}</p>
                            @if (auth()->user()?->is_admin)
                                <a href="#upload-form" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                    </svg>
                                    {{ config('messages.dashboard.upload_cta_button') }}
                                </a>
                            @endif
                        </div>
                    @else
                        @php
                            // 削除ボタン表示判定をループ外で一度だけ行う
                            $isAdmin = auth()->user()?->is_admin ?? false;
                        @endphp

                        <div class="space-y-4">
                            @foreach ($documents as $document)
                                @php
                                    // ステータスのラベルとバッジクラスをconfigから取得する
                                    $statusLabels      = config('inask.document_status_labels', []);
                                    $statusBadgeClasses = config('inask.document_status_badge_classes', []);
                                    $statusLabel = $statusLabels[$document->status] ?? $document->status;
                                    $badgeClass  = $statusBadgeClasses[$document->status] ?? 'bg-gray-100 text-gray-800';
                                @endphp

                                {{-- ドキュメントカード --}}
                                <div class="border border-gray-200 rounded-lg overflow-hidden">

                                    {{-- カードヘッダー（ドキュメント情報） --}}
                                    <div class="flex items-center justify-between px-4 py-3 bg-gray-50">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="text-sm font-medium text-gray-900 truncate">{{ $document->title }}</span>
                                            <span class="hidden sm:inline text-xs text-gray-400 shrink-0">{{ $document->mime_type }}</span>
                                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium shrink-0 {{ $badgeClass }}">
                                                {{ $statusLabel }}
                                            </span>
                                            <span class="hidden sm:inline text-xs text-gray-400 shrink-0">{{ $document->created_at->format('Y/m/d H:i') }}</span>
                                        </div>
                                        @if ($isAdmin)
                                            <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('削除しますか？')" class="ml-4 shrink-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-medium">{{ config('messages.dashboard.delete_button') }}</button>
                                            </form>
                                        @endif
                                    </div>

                                    {{-- FAQセクション --}}
                                    <div class="px-4 py-3">
                                        @if ($document->status === config('inask.document_status.done'))
                                            @if ($document->faqs->isEmpty())
                                                {{-- done済みだがFAQ未生成（生成ジョブが未完了または失敗） --}}
                                                <div class="flex items-center gap-2 text-xs text-gray-400">
                                                    <svg class="w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                                    </svg>
                                                    {{ config('errors.dashboard.faq_not_generated') }}
                                                </div>
                                            @else
                                                {{-- FAQアコーディオン（details/summaryでJSなし実装） --}}
                                                <div class="space-y-1" data-faq-section>
                                                    @foreach ($document->faqs as $faq)
                                                        <details class="group border border-gray-100 rounded">
                                                            <summary class="flex items-center justify-between px-3 py-2 cursor-pointer select-none list-none text-sm font-medium text-gray-800 hover:bg-gray-50 rounded">
                                                                <span>{{ $faq->question }}</span>
                                                                {{-- 開閉アイコン --}}
                                                                <svg class="w-4 h-4 text-gray-400 shrink-0 ml-2 transition-transform group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                                </svg>
                                                            </summary>
                                                            <div class="px-3 py-2 text-sm text-gray-600 border-t border-gray-100 bg-white">
                                                                {{ $faq->answer }}
                                                            </div>
                                                        </details>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @elseif ($document->status === config('inask.document_status.failed'))
                                            {{-- 処理失敗 --}}
                                            <div class="flex items-center gap-2 text-xs text-red-400">
                                                <svg class="w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                </svg>
                                                {{ config('errors.dashboard.faq_failed') }}
                                            </div>
                                        @else
                                            {{-- pending / processing：アニメーションドットで処理中を表現する --}}
                                            <div class="flex items-center gap-2 text-xs text-yellow-600">
                                                <span class="relative flex h-2 w-2 shrink-0">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-yellow-500"></span>
                                                </span>
                                                {{ config('errors.dashboard.faq_processing') }}
                                            </div>
                                        @endif
                                    </div>

                                </div>
                            @endforeach
                        </div>

                        {{-- ページネーションリンク --}}
                        @if ($documents->hasPages())
                            <div class="mt-4">
                                {{ $documents->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- pending/processing中のドキュメントがある場合は3秒おきに自動リロードする --}}
    @if ($hasPending)
        <script>
            setTimeout(function () {
                location.reload();
            }, 3000);
        </script>
    @endif
</x-app-layout>
