# ロリポップ本番自動デプロイ

`main` の Site quality が成功した場合だけ、`Deploy production` が本番へ反映します。初期状態では無効です。設定直後の初回だけ、Actions画面の `Run workflow` からmainを手動デプロイできます。

## GitHub Environment `production` に登録する値

Secrets（値を画面やチャットへ貼らない）:

- `LOLIPOP_FTP_HOST`
- `LOLIPOP_FTP_USER`
- `LOLIPOP_FTP_PASSWORD`

Variables:

- `LOLIPOP_FTP_SCHEME`: explicit FTPSは `ftp`、implicit FTPSは `ftps`
- `LOLIPOP_FTP_PORT`: ロリポップ管理画面に表示されたポート
- `LOLIPOP_DEPLOY_DIR`: `rss7.net` の正確な公開先ディレクトリ（`/`は禁止）
- `LOLIPOP_SITE_URL`: `https://rss7.net`
- `LOLIPOP_DEPLOY_ENABLED`: 動作確認が終わるまで `false`、本番開始時だけ `true`

## 有効化前の必須確認

1. `api/config.example.php` を参考に、本番だけに `api/config.local.php` を作る。過去の平文パスワードは再利用しない。
2. `private_data_dir` は、すべての `public_html` / DocumentRoot の外側に設定する。
3. PHP 7.4以上、`mbstring`、`fileinfo`、`DOM` が利用できることを確認する。
4. `images/blog/` と `private_data_dir` をPHPから書き込めるようにする。
5. `https://rss7.net/data/articles.json` がHTTP 403になることを確認する。
6. ロリポップ側の現行 `rss7.net` をバックアップし、公開先ディレクトリと `LOLIPOP_SITE_URL` を再確認する。
7. `LOLIPOP_DEPLOY_ENABLED=true` にする。
8. GitHub Actionsの `Deploy production` を開き、mainを選んで `Run workflow` を1回実行する。手動実行でも全品質検査に合格しなければ転送されない。

## 公開後の自動確認

転送後、ワークフローが自動で次を検査します。

- お問い合わせページが新しい送信ボタンを表示する
- お問い合わせAPIが正常に起動し、CSRFトークンを返す
- 記事APIが公開領域外の正本を読み込める
- `data/articles.json` がHTTP 403で保護されている

検査に失敗した場合、デプロイは成功扱いになりません。実際のメール到達だけは、初回に人がテスト送信して確認します。

## 保護対象

自動デプロイは削除同期を行わず、次をアップロード対象から除外します。

- `api/config.local.php`
- `data/articles.json`
- `images/blog/` 内の画像ファイル（`.htaccess` は配布）
- `.git` / `.github` / `docs` / `scripts`
- ZIPファイル

これにより、本番のパスワード設定、既存記事、管理画面から追加した画像を保護します。
