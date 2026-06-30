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
];
