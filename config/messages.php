<?php

// 画面表示用のUIメッセージ（エラーではない案内文・ラベル・見出しなど）
// errors.php はバリデーションエラー・API障害などの異常系メッセージを管理する
// messages.php は正常系のUI文言（見出し・説明文・ボタンラベルなど）を管理する

return [

    // ドキュメント一覧画面
    'documents' => [
        'page_title'            => '社内ドキュメント一覧',
        'nav_link'              => '社内ドキュメント一覧',
        'processing_notice'     => '処理中のドキュメントがあります。自動更新中…',
        'upload_section_title'  => 'ドキュメントをアップロード',
        'file_label'            => 'ファイルを選択',
        'upload_button'         => 'アップロード',
        'upload_cta_button'     => 'アップロードする',
        'delete_button'         => '削除',
        'keyword_placeholder'   => '社内ドキュメントをタイトル・内容で絞り込む',
        'keyword_button'        => '絞り込み',
        'keyword_clear'         => 'クリア',
        'no_results'            => '該当するドキュメントが見つかりませんでした',
    ],

    // ドキュメント詳細・FAQ画面
    'document_show' => [
        'faq_section_title'   => 'よくある質問（FAQ）',
        'back_link'           => '← 一覧に戻る',
        'no_faq'              => 'FAQはまだ生成されていません',
    ],

    // 検索画面
    'search' => [
        'page_title'            => 'ドキュメント内容検索',
        'query_label'           => '質問を入力してください',
        'query_placeholder'     => '例：有給休暇の申請方法は？',
        'search_button'         => '検索',
        'nav_link'              => 'ドキュメント内容検索',
        // 検索前の初期ガイダンス
        'guidance_title'        => '社内ドキュメントに質問してみましょう',
        'guidance_description'  => '登録されているドキュメントをもとに、AIが回答を生成します',
        // 検索例（検索前に表示するサンプルクエリ）
        'example_queries' => [
            '有給休暇の申請方法は？',
            '経費精算の締め日はいつ？',
            '入社初日の持ち物は？',
        ],
        // 回答・出典セクション
        'answer_section_title'  => '回答',
        'sources_section_title' => '参照ドキュメント（%d件）',
    ],

];
