<?php

return [
    'document_status' => [
        'pending'    => 'pending',
        'processing' => 'processing',
        'done'       => 'done',
        'failed'     => 'failed',
    ],

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
