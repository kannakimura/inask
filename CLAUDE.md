# Innask 開発ルール

## ブランチ・PRルール

- 作業は必ず最新のmainブランチから`feature/phase{N}-{内容}`ブランチを切って進める
- **Phaseが完了したらPRを作成する**（mainへのdirect pushは禁止）
- PRのタイトルは`Phase {N}: {内容}`の形式
- @codex review でCodexにコードレビューを依頼する

## コミットルール

- 1機能1コミット
- コミットメッセージは`Phase {N}-{タスク番号}: {内容}`の形式
- 例: `Phase 2-1: Add Document model`
