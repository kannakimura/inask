# inask

社内ドキュメントから FAQ を自動生成し、自然言語で検索できる RAG（Retrieval-Augmented Generation）ツールのデモアプリケーションです。

> **このアプリはデモ用途で作成されています。**  
> 面接・ポートフォリオ・学習目的での利用を想定しており、本番運用には対応していません。

---

## 機能

- **ドキュメントアップロード**（PDF・テキスト・Markdown）
- **自動チャンキング＋ベクトル埋め込み**（Voyage AI）
- **FAQ 自動生成**（Claude API）
- **RAG 検索**：質問文を入力すると関連チャンクを検索し、Claude が回答を生成

---

## 技術スタック

| 種別 | 使用技術 |
|---|---|
| バックエンド | Laravel 11 / PHP 8.3 |
| 認証 | Laravel Breeze |
| データベース | PostgreSQL 16 + pgvector |
| キャッシュ・キュー | Redis |
| フロントエンド | Blade / Tailwind CSS / Vite |
| Web サーバー | Nginx / PHP-FPM |
| AI（埋め込み） | Voyage AI (`voyage-3`) |
| AI（回答生成） | Anthropic Claude (`claude-sonnet-4-6`) |
| インフラ | Docker / Docker Compose |

---

## セットアップ

Docker を使った起動手順は **[inask-docker](https://github.com/kannakimura/inask-docker)** を参照してください。

---

## 使い方

### ログイン

[http://localhost:8080/login](http://localhost:8080/login) を開いてください。

デモ用アカウントでログインします。

| 項目 | 値 |
|---|---|
| メールアドレス | `demo@innask.local` |
| パスワード | `password` |

---

### ドキュメントをアップロードする

1. ログイン後、ダッシュボードのアップロードフォームからファイルを選択
2. 「アップロード」ボタンをクリック
3. バックグラウンドで自動処理が始まります（ベクトル化 → FAQ 生成）
4. ステータスが「完了」になると FAQ が表示されます（ページは自動リロードされます）

対応ファイル形式：PDF・テキスト（.txt）・Markdown（.md）

---

### 社内ドキュメントを検索する

1. ナビゲーションの「検索」をクリック
2. 質問文を入力して「検索」ボタンをクリック
3. 関連ドキュメントをもとに AI が回答を生成します
4. 回答下部の「参照ドキュメント」から出典を確認できます

質問例：
- 「有給休暇の申請方法は？」
- 「経費精算の締め日はいつ？」
- 「入社初日の持ち物は？」

---

## テストの実行

`inask-docker/` ディレクトリ内で実行してください。

```bash
docker compose exec app php artisan test
```
