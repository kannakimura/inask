<?php

return [
    'document_status' => [
        'pending'    => 'pending',
        'processing' => 'processing',
        'done'       => 'done',
        'failed'     => 'failed',
    ],

    // アップロード可能な最大ファイルサイズ（KB単位）
    // 1MBに制限する（大きすぎるとJob内でVoyage APIを大量呼び出ししてタイムアウトするため）
    'max_upload_size_kb' => env('MAX_UPLOAD_SIZE_KB', 1024),

    'supported_mime_types' => [
        'application/pdf',
        'text/plain',
        'text/markdown',
    ],

    'chunk' => [
        'size'    => 500,
        'overlap' => 50,
    ],

    'embedding' => [
        'model'      => 'voyage-3',
        'dimensions' => 1024,
        // Voyage APIのinput上限は1000件のため余裕をもって128件に設定する
        'batch_size' => 128,
        // Job timeoutと整合させるためのチャンク数上限
        // 500チャンク → ceil(500/128)=4バッチ、最悪4×90秒=360秒 → timeout=600秒と一致させる
        'max_chunks' => 500,
    ],

    'search' => [
        'top_k' => 5,
    ],
];
