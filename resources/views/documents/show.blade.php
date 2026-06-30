<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('documents.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium shrink-0">
                {{ config('messages.document_show.back_link') }}
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight truncate">
                {{ $document->title }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ドキュメント情報 --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 flex items-center gap-4 text-sm text-gray-500">
                    @php
                        $statusLabels       = config('inask.document_status_labels', []);
                        $statusBadgeClasses = config('inask.document_status_badge_classes', []);
                        $statusLabel = $statusLabels[$document->status] ?? $document->status;
                        $badgeClass  = $statusBadgeClasses[$document->status] ?? 'bg-gray-100 text-gray-800';
                    @endphp
                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $badgeClass }}">{{ $statusLabel }}</span>
                    <span>{{ $document->mime_type }}</span>
                    <span>{{ $document->created_at->format('Y/m/d H:i') }}</span>
                </div>
            </div>

            {{-- FAQセクション --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">{{ config('messages.document_show.faq_section_title') }}</h3>

                    @if ($document->status === config('inask.document_status.done'))
                        @if ($document->faqs->isEmpty())
                            <p class="text-sm text-gray-400">{{ config('messages.document_show.no_faq') }}</p>
                        @else
                            <div class="space-y-2">
                                @foreach ($document->faqs as $faq)
                                    <details class="group border border-gray-100 rounded-lg">
                                        <summary class="flex items-center justify-between px-4 py-3 cursor-pointer select-none list-none text-sm font-medium text-gray-800 hover:bg-gray-50 rounded-lg">
                                            <span>{{ $faq->question }}</span>
                                            <svg class="w-4 h-4 text-gray-400 shrink-0 ml-3 transition-transform group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </summary>
                                        <div class="px-4 py-3 text-sm text-gray-600 border-t border-gray-100 bg-gray-50 rounded-b-lg whitespace-pre-wrap leading-relaxed">{{ $faq->answer }}</div>
                                    </details>
                                @endforeach
                            </div>
                        @endif
                    @elseif ($document->status === config('inask.document_status.failed'))
                        <div class="flex items-center gap-2 text-sm text-red-400">
                            <svg class="w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                            {{ config('errors.dashboard.faq_failed') }}
                        </div>
                    @else
                        {{-- pending/processing --}}
                        <div class="flex items-center gap-2 text-sm text-yellow-600">
                            <span class="relative flex h-2 w-2 shrink-0">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-yellow-500"></span>
                            </span>
                            {{ config('errors.dashboard.faq_processing') }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
