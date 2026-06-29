<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
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

        </div>
    </div>
</x-app-layout>
