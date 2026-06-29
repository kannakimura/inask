<?php

return [
    'document_status' => [
        'pending'    => 'pending',
        'processing' => 'processing',
        'done'       => 'done',
        'failed'     => 'failed',
    ],

    // アップロード可能な最大ファイルサイズ（KB単位）
    'max_upload_size_kb' => env('MAX_UPLOAD_SIZE_KB', 10240),

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
        'model'   => 'voyage-3',
        'dimensions' => 1024,
    ],

    'search' => [
        'top_k' => 5,
    ],
];
