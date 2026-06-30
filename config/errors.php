<?php

return [
    // ファイルアップロード関連のエラーメッセージ
    'file' => [
        'required'        => 'ファイルを選択してください。',
        'file'            => '有効なファイルをアップロードしてください。',
        'mimetypes'       => 'PDF・テキスト・Markdownファイルのみアップロードできます。',
        'store_failed'    => 'ファイルの保存に失敗しました。',
        'deletion_failed' => 'ファイル削除に失敗しました（孤立ファイル）',
    ],

    // テキスト抽出関連のエラーメッセージ
    'extract' => [
        'unsupported_mime' => '未対応のMIMEタイプです',
        'read_failed'      => 'ファイルの読み込みに失敗しました',
    ],

    // ProcessDocumentJob関連のメッセージ
    'process_document' => [
        'completed'      => 'ドキュメントの処理が完了しました',
        'failed'         => 'ドキュメントの処理に失敗しました',
        'enqueue_failed' => 'Jobのenqueueに失敗しました。ドキュメントを削除しました。',
    ],

    // EmbeddingService関連のエラーメッセージ
    'embedding' => [
        'empty_chunks' => 'チャンクが空のため保存できません',
    ],

    // Voyage AI embedding関連のエラーメッセージ
    'voyage' => [
        'api_key_missing'  => 'VOYAGE_API_KEYが設定されていません',
        'request_failed'   => 'Voyage AIへのリクエストに失敗しました',
        'invalid_response' => 'Voyage AIから不正なレスポンスが返されました',
    ],
];
