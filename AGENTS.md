# RSS7 AI Works エージェント運用ルール

このリポジトリは、複数のAIエージェントでRSS7 AI Worksを安全に保守するための公開リポジトリです。

## 必須ルール

1. `main` へ直接コミットしない。エージェントごとの作業ブランチからPRを作成する。
2. 作成後に差分確認、自己評価、修正、動作確認を行ってからPRを提出する。
3. `.env`、APIキー、パスワード、メールフォーム設定、FTP情報、秘密鍵を登録しない。
4. 元ZIP、バックアップ、ログ、サーバー固有設定、未監査のPHP・CGIを登録しない。
5. 本番サーバーへ全同期や削除同期を行わない。公開は明示されたファイルだけを対象にする。
6. `data/articles.json` と公開HTMLの互換性を保つ。文字コードはUTF-8を基本とする。
7. 既存ページのURL、SEO情報、Google確認ファイル、`robots.txt`、`sitemap.xml` を無断で変更しない。

## 推奨ブランチ

- Claude: `feat/claude-archive`
- Gemini: `feat/gemini-archive`
- Codex: `feat/codex-archive`

## 完了報告

PRには、変更目的、変更ファイル、確認結果、既知の問題、戻し方を記載する。
