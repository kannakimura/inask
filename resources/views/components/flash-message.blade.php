{{--
    フラッシュメッセージコンポーネント
    session('success') / session('error') / session('warning') を自動検出して表示する
    各タイプにアイコンと配色を割り当て、視覚的に区別しやすくする
--}}
@php
    // 表示すべきフラッシュメッセージとそのタイプを判定する
    $flashes = [
        'success' => session('success'),
        'error'   => session('error'),
        'warning' => session('warning'),
    ];

    // タイプごとの配色・アイコン設定
    $styles = [
        'success' => [
            'wrapper' => 'bg-green-50 border border-green-300 text-green-800',
            'icon'    => 'text-green-500',
        ],
        'error' => [
            'wrapper' => 'bg-red-50 border border-red-300 text-red-800',
            'icon'    => 'text-red-500',
        ],
        'warning' => [
            'wrapper' => 'bg-yellow-50 border border-yellow-300 text-yellow-800',
            'icon'    => 'text-yellow-500',
        ],
    ];
@endphp

@foreach ($flashes as $type => $message)
    @if ($message)
        <div class="rounded-lg p-4 flex items-start gap-3 {{ $styles[$type]['wrapper'] }}" role="alert">
            {{-- タイプ別アイコン --}}
            @if ($type === 'success')
                <svg class="w-5 h-5 shrink-0 mt-0.5 {{ $styles[$type]['icon'] }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            @elseif ($type === 'error')
                <svg class="w-5 h-5 shrink-0 mt-0.5 {{ $styles[$type]['icon'] }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            @elseif ($type === 'warning')
                <svg class="w-5 h-5 shrink-0 mt-0.5 {{ $styles[$type]['icon'] }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            @endif
            <p class="text-sm font-medium">{{ $message }}</p>
        </div>
    @endif
@endforeach
