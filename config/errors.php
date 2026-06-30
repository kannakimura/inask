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
        'skipped'        => 'Jobはすでに処理済みまたは処理中のためスキップしました',
    ],

    // EmbeddingService関連のエラーメッセージ
    'embedding' => [
        'empty_chunks'    => 'チャンクが空のため保存できません',
        'too_many_chunks' => 'チャンク数がmax_chunksの上限を超えています',
    ],

    // Voyage AI embedding関連のエラーメッセージ
    'voyage' => [
        'api_key_missing'  => 'VOYAGE_API_KEYが設定されていません',
        'request_failed'   => 'Voyage AIへのリクエストに失敗しました',
        'invalid_response' => 'Voyage AIから不正なレスポンスが返されました',
    ],

    // Anthropic Claude API関連のエラーメッセージ
    'claude' => [
        'api_key_missing'  => 'ANTHROPIC_API_KEYが設定されていません',
        'request_failed'   => 'Claude APIへのリクエストに失敗しました',
        'invalid_response' => 'Claude APIから不正なレスポンスが返されました',
    ],

    // FaqGeneratorService関連のエラーメッセージ
    'faq_generator' => [
        'empty_chunks'     => 'チャンクが空のためFAQを生成できません',
        'invalid_json'     => 'Claude APIのレスポンスをJSONとしてパースできませんでした',
        'invalid_format'   => 'Claude APIのレスポンスに必要なquestion/answerフィールドがありません',
    ],

    // AnswerGeneratorService関連のエラーメッセージ
    'answer_generator' => [
        'no_sources' => '関連するドキュメントが見つかりませんでした。別のキーワードで検索してください。',
    ],

    // 検索クエリバリデーション関連のエラーメッセージ
    'search' => [
        'query_required' => '検索キーワードを入力してください。',
        'query_string'   => '検索キーワードは文字列で入力してください。',
        'query_max'      => '検索キーワードは200文字以内で入力してください。',
    ],

    // GenerateFaqsJob関連のメッセージ
    'generate_faqs' => [
        'completed' => 'FAQの自動生成が完了しました',
        'failed'    => 'FAQの自動生成に失敗しました',
        'skipped'   => 'チャンクが存在しないためFAQ生成をスキップしました',
    ],
];
