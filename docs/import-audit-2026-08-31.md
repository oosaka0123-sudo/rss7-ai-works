# 元ZIP取り込み監査 — 2026-08-31

## 結果

- 元ZIP: `rss7ainet.zip`
- 元ZIP内のファイル数: 257
- `main` 登録済みファイル数: 53
- 未登録ファイル数: 214

未登録214ファイルは単純なアップロード漏れではない。旧サイト資産、サーバー固有設定、ログ、バックアップ、実行コード、内包ZIPなどを含むため、公開リポジトリへの一括登録を見送った。

## 登録禁止または要個別監査

- `.htaccess`、`php.ini`
- `auto_post.php`、`data/auto_post.php`
- `mailform2/`、`mailform_b/`
- `upload.php`、`test.php`、`marlong.php`、`mtview.php*`
- `*.log`、`*.bak`、`*.dat`
- `rss7-complete.zip`
- 認証情報、APIキー、パスワード、送信先情報を含む可能性があるファイル

## 方針

必要な旧画像や静的ページは、利用箇所と権利関係を確認したうえで個別PRとして追加する。元ZIPをそのままコミットしない。
