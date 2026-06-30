<?php

// 画面表示用のUIメッセージ（エラーではない案内文・ラベル・見出しなど）
// errors.php はバリデーションエラー・API障害などの異常系メッセージを管理する
// messages.php は正常系のUI文言（見出し・説明文・ボタンラベルなど）を管理する

return [

    // ダッシュボード（ドキュメント一覧画面）
    'dashboard' => [
        'page_title'            => 'Dashboard',
        'processing_notice'     => '処理中のドキュメントがあります。自動更新中…',
        'upload_section_title'  => 'ドキュメントをアップロード',
        'file_label'            => 'ファイルを選択',
        'upload_button'         => 'アップロード',
        'upload_cta_button'     => 'アップロードする',
        'list_section_title'    => 'ドキュメント一覧',
        'delete_button'         => '削除',
    ],

    // 検索画面
    'search' => [
        'page_title'            => '社内ドキュメント検索',
        'query_label'           => '質問を入力してください',
        'query_placeholder'     => '例：有給休暇の申請方法は？',
        'search_button'         => '検索',
        'nav_link'              => '検索',
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
