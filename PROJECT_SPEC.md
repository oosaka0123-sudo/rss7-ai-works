# RSS7 AI Works プロジェクト仕様

## 目的

RSS7 AI Worksの公開サイトをGitHubで管理し、Claude、Gemini、Codexなど複数のAIエージェントが安全に改善できる状態にする。

## 現在の構成

- 公開用HTML
- 公開ブログ記事
- 公開画像
- `data/articles.json`
- `robots.txt` と `sitemap.xml`

## 公開環境

現行サイトはロリポップ側で稼働している。GitHub PagesだけではPHP、認証API、自動投稿処理は実行できない。

## 重要な制約

- このリポジトリはサーバー全体の完全バックアップではない。
- 本番ファイルを一括削除・一括置換しない。
- API、自動投稿、FTPなどの秘密情報はGitHub Secretsまたはサーバー環境変数で管理する。
- 受領ZIP内の旧メールフォーム、ログ、バックアップ、未監査PHP、サーバー設定、内包ZIPは公開しない。

## 既知の不足

- `api/articles.php`
- `api/auth.php`
- `api/upload.php`
- `images/og-image.jpg`

不足ファイルは稼働サーバーから別途取得し、秘密情報を除去・分離してから追加する。
