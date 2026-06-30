# Innask

社内ドキュメントから FAQ を自動生成し、自然言語で検索できる RAG（Retrieval-Augmented Generation）ツールのデモアプリケーションです。

> **このアプリはデモ用途で作成されています。**  
> 面接・ポートフォリオ・学習目的での利用を想定しており、本番運用には対応していません。

---

## 機能

- **ドキュメントアップロード**（PDF・テキスト・Markdown）
- **自動チャンキング＋ベクトル埋め込み**（Voyage AI）
- **FAQ 自動生成**（Claude API）
- **RAG 検索**：質問文を入力すると関連チャンクを検索し、Claude が回答を生成
- **デモデータ**：`php artisan db:seed` 一発でサンプルドキュメント3件が投入できます

---

## 技術スタック

| 種別 | 使用技術 |
|---|---|
| バックエンド | Laravel 11 / PHP 8.3 |
| データベース | PostgreSQL 16 + pgvector |
| キャッシュ・キュー | Redis |
| フロントエンド | Blade / Tailwind CSS |
| AI（埋め込み） | Voyage AI (`voyage-3`) |
| AI（回答生成） | Anthropic Claude (`claude-sonnet-4-6`) |
| インフラ | Docker / Docker Compose |

---

## 事前準備

以下のものを用意してください。

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) がインストール済みであること
- **Voyage AI の API キー**（[voyageai.com](https://www.voyageai.com/) で取得）
- **Anthropic の API キー**（[console.anthropic.com](https://console.anthropic.com/) で取得）

---

## セットアップ手順

### 1. Docker 設定リポジトリをクローン

```bash
git clone https://github.com/kannakimura/inask-docker.git
cd inask-docker
```

### 2. セットアップスクリプトを実行

アプリ本体のクローンと `.env` ファイルの作成を自動で行います。

```bash
./setup.sh
```

実行後のディレクトリ構成：

```
任意のディレクトリ/
├── inask-docker/   ← Docker 設定（今いる場所）
└── inask/          ← Laravel アプリ本体（自動生成）
```

### 3. API キーを設定

`../inask/.env` をエディタで開き、以下の2行に取得した API キーを入力してください。

```env
VOYAGE_API_KEY=your_voyage_api_key_here
ANTHROPIC_API_KEY=your_anthropic_api_key_here
```

その他の値（DB・Redis など）はデフォルトのままで動作します。

### 4. Docker コンテナを起動

`inask-docker/` ディレクトリ内で実行してください。

```bash
docker compose up -d --build
```

初回はイメージのビルドに数分かかります。

### 5. Composer パッケージをインストール

```bash
docker compose exec app composer install
```

### 6. アプリケーションキーを生成

```bash
docker compose exec app php artisan key:generate
```

### 7. データベースのマイグレーション

```bash
docker compose exec app php artisan migrate
```

### 8. デモデータを投入

Voyage AI でベクトルを生成しながら3件のサンプルドキュメントを投入します（約30秒かかります）。

```bash
docker compose exec app php artisan db:seed
```

投入されるデモデータ：
- 就業規則（有給休暇・フレックス・テレワークなど）
- 経費精算ガイドライン（精算フロー・交通費・接待費など）
- オンボーディングガイド（入社初日・使用ツール・福利厚生など）

### 9. フロントエンドビルド

```bash
docker compose exec app npm install
docker compose exec app npm run build
```

### 10. ブラウザでアクセス

[http://localhost:8080/login](http://localhost:8080/login) を開いてください。

---

## ログイン情報

デモ用アカウントは `db:seed` で自動作成されます。

| 項目 | 値 |
|---|---|
| メールアドレス | `demo@innask.local` |
| パスワード | `password` |

---

## 使い方

### ドキュメントをアップロードする（管理者のみ）

1. ログイン後、ダッシュボードに表示されるアップロードフォームからファイルを選択
2. 「アップロード」ボタンをクリック
3. バックグラウンドで自動処理が始まります（ベクトル化 → FAQ生成）
4. ステータスが「完了」になると FAQ が表示されます（ページは自動リロードされます）

対応ファイル形式：PDF・テキスト（.txt）・Markdown（.md）

### 社内ドキュメントを検索する

1. ナビゲーションの「検索」をクリック
2. 質問文を入力して「検索」ボタンをクリック
   - 例：「有給休暇の申請方法は？」
   - 例：「経費精算の締め日はいつ？」
3. 関連ドキュメントから AI が回答を生成します
4. 回答下部の「参照ドキュメント」から出典チャンクを確認できます

---

## テストの実行

`inask-docker/` ディレクトリ内で実行してください。

```bash
docker compose exec app php artisan test
```

---

## コンテナの停止

```bash
docker compose down
```

データベースのデータも含めて完全にリセットする場合：

```bash
docker compose down -v
```
