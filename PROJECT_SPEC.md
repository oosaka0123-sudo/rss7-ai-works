# RSS7 AI Works プロジェクト仕様

## 目的

RSS7 AI Worksの公開サイトをGitHubで管理し、Claude、Gemini、Codexなど複数のAIエージェントが安全に改善できる状態にする。

## 現在の構成

- 公開用HTML
- 公開ブログ記事
- 公開画像
- `data/articles.json`
- `robots.txt` と `sitemap.xml`
- PHP API（`api/articles.php`、`api/auth.php`、`api/upload.php`、`api/contact.php` など）
- OGP画像 `images/og-image.jpg`

## 公開環境

現行サイトはロリポップ側で稼働している。GitHub PagesだけではPHP、認証API、自動投稿処理は実行できない。

本番デプロイは `.github/workflows/deploy-production.yml` で管理し、Site quality合格後に安全条件を満たした場合のみロリポップへ反映する。

## 重要な制約

- このリポジトリはサーバー全体の完全バックアップではない。
- 本番ファイルを一括削除・一括置換しない。
- API、自動投稿、FTPなどの秘密情報はGitHub Secretsまたはサーバー環境変数で管理する。
- 受領ZIP内の旧メールフォーム、ログ、バックアップ、未監査PHP、サーバー設定、内包ZIPは公開しない。
- 本番専用の `api/config.local.php` はGitHubへコミットしない。

## 現在の確認済み状態

- `api/articles.php`：存在確認済み
- `api/auth.php`：存在確認済み
- `api/upload.php`：存在確認済み
- `images/og-image.jpg`：存在確認済み

今後の不足や既知の問題は、実際のmain・Issue・PR・Actionsを確認したうえで更新する。古い不足一覧を固定的に残さない。
