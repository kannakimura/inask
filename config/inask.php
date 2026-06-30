<?php

return [
    'document_status' => [
        'pending'    => 'pending',
        'processing' => 'processing',
        'done'       => 'done',
        'failed'     => 'failed',
    ],

    // ステータスの表示ラベル（UI表示用）
    'document_status_labels' => [
        'pending'    => '待機中',
        'processing' => '処理中',
        'done'       => '完了',
        'failed'     => '失敗',
    ],

    // ステータスバッジのTailwindクラス（UI表示用）
    'document_status_badge_classes' => [
        'pending'    => 'bg-gray-100 text-gray-800',
        'processing' => 'bg-yellow-100 text-yellow-800',
        'done'       => 'bg-green-100 text-green-800',
        'failed'     => 'bg-red-100 text-red-800',
    ],

    // アップロード可能な最大ファイルサイズ（KB単位）
    // max_chunksと整合させる: max_chunks(500) × (chunk.size-overlap)(450文字) ≈ 225,000文字 ≈ 220KB
    // この値を超えると ProcessDocumentJob が too_many_chunks で必ず failed になるため
    'max_upload_size_kb' => env('MAX_UPLOAD_SIZE_KB', 220),

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

    // フラッシュメッセージのセッションキー一覧
    // flash-messageコンポーネントはこのキーを順に参照して表示する
    'flash_types' => ['success', 'error', 'warning'],

    // Claude API（FAQ生成）の設定
    'claude' => [
        // FAQ生成に使用するモデル
        'model'      => 'claude-sonnet-4-6',
        // 1リクエストあたりの最大出力トークン数
        // FAQ 5件 × (質問100トークン + 回答200トークン) ≈ 1500トークン + JSON構造のオーバーヘッド
        'max_tokens' => 2048,
        // ドキュメント1件あたりに生成するFAQの件数
        'faq_count'  => 5,
    ],
];
