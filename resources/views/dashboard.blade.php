<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
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
                    処理中のドキュメントがあります。自動更新中…
                </span>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- フラッシュメッセージ（成功） --}}
            @if (session('success'))
                <div class="bg-green-50 border border-green-300 text-green-800 rounded-lg p-4">
                    {{ session('success') }}
                </div>
            @endif

            {{-- フラッシュメッセージ（エラー） --}}
            @if (session('error'))
                <div class="bg-red-50 border border-red-300 text-red-800 rounded-lg p-4">
                    {{ session('error') }}
                </div>
            @endif

            {{-- ドキュメントアップロードフォーム（adminのみ表示） --}}
            @if (auth()->user()?->is_admin)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">ドキュメントをアップロード</h3>

                    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- ファイル選択 --}}
                        <div class="mb-4">
                            <label for="file" class="block text-sm font-medium text-gray-700 mb-1">
                                ファイルを選択
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
                                アップロード
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            {{-- ドキュメント一覧 --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">ドキュメント一覧</h3>

                    @if ($documents->isEmpty())
                        <p class="text-gray-500 text-sm">アップロードされたドキュメントはありません。</p>
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
                                                <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-medium">削除</button>
                                            </form>
                                        @endif
                                    </div>

                                    {{-- FAQセクション --}}
                                    <div class="px-4 py-3">
                                        @if ($document->status === config('inask.document_status.done'))
                                            @if ($document->faqs->isEmpty())
                                                {{-- done済みだがFAQ未生成（生成ジョブが未完了または失敗） --}}
                                                <p class="text-xs text-gray-400">FAQはまだ生成されていません。</p>
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
                                            <p class="text-xs text-red-400">処理に失敗したため、FAQを生成できませんでした。</p>
                                        @else
                                            {{-- pending / processing --}}
                                            <p class="text-xs text-gray-400">ベクトル化処理が完了するとFAQが表示されます。</p>
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
