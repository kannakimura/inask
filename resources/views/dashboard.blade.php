<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard
        </h2>
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

            {{-- ドキュメントアップロードフォーム --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">ドキュメントをアップロード</h3>

                    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- ファイル選択 --}}
                        <div class="mb-4">
                            <label for="file" class="block text-sm font-medium text-gray-700 mb-1">
                                ファイルを選択
                                <span class="text-gray-400 font-normal">（PDF・テキスト・Markdown、最大{{ config('inask.max_upload_size_kb', 10240) / 1024 }}MB）</span>
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

            {{-- ドキュメント一覧 --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">ドキュメント一覧</h3>

                    @if ($documents->isEmpty())
                        <p class="text-gray-500 text-sm">アップロードされたドキュメントはありません。</p>
                    @else
                        @php
                            // 削除カラム表示判定をループ外で一度だけ行う
                            $isAdmin = auth()->user()?->is_admin ?? false;
                        @endphp
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ファイル名</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">種別</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">登録日時</th>
                                    @if ($isAdmin)
                                        <th class="px-4 py-3"></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($documents as $document)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $document->title }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $document->mime_type }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            {{-- ステータスバッジ --}}
                                            @php
                                                $badgeClass = match($document->status) {
                                                    'done'       => 'bg-green-100 text-green-800',
                                                    'processing' => 'bg-yellow-100 text-yellow-800',
                                                    'failed'     => 'bg-red-100 text-red-800',
                                                    default      => 'bg-gray-100 text-gray-800',
                                                };
                                                $statusLabel = match($document->status) {
                                                    'done'       => '完了',
                                                    'processing' => '処理中',
                                                    'failed'     => '失敗',
                                                    default      => '待機中',
                                                };
                                            @endphp
                                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $badgeClass }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $document->created_at->format('Y/m/d H:i') }}</td>
                                        @if ($isAdmin)
                                            <td class="px-4 py-3 text-right">
                                                <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('削除しますか？')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-medium">削除</button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

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
</x-app-layout>
