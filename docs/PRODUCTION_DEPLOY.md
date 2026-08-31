# ロリポップ本番自動デプロイ

`main` の Site quality が成功した場合だけ、`Deploy production` が本番へ反映します。初期状態では無効です。

## GitHub Environment `production` に登録する値

Secrets（値を画面やチャットへ貼らない）:

- `LOLIPOP_FTP_HOST`
- `LOLIPOP_FTP_USER`
- `LOLIPOP_FTP_PASSWORD`

Variables:

- `LOLIPOP_FTP_SCHEME`: explicit FTPSは `ftp`、implicit FTPSは `ftps`
- `LOLIPOP_FTP_PORT`: ロリポップ管理画面に表示されたポート
- `LOLIPOP_DEPLOY_DIR`: `rss7.net` の正確な公開先ディレクトリ（`/`は禁止）
- `LOLIPOP_DEPLOY_ENABLED`: 動作確認が終わるまで `false`、本番開始時だけ `true`

## 有効化前の必須確認

1. `api/config.example.php` を参考に、本番だけに `api/config.local.php` を作る。過去の平文パスワードは再利用しない。
2. `private_data_dir` は、すべての `public_html` / DocumentRoot の外側に設定する。
3. PHP 7.4以上、`mbstring`、`fileinfo`、`DOM` が利用できることを確認する。
4. `images/blog/` と `private_data_dir` をPHPから書き込めるようにする。
5. `https://rss7.net/data/articles.json` がHTTP 403になることを確認する。
6. 最初は空のテスト用ディレクトリを `LOLIPOP_DEPLOY_DIR` に指定し、転送先を確認する。
7. 本番ディレクトリへ戻した後、`LOLIPOP_DEPLOY_ENABLED=true` にする。

## 保護対象

自動デプロイは削除同期を行わず、次をアップロード対象から除外します。

- `api/config.local.php`
- `data/articles.json`
- `images/blog/`
- `.git` / `.github` / `docs` / `scripts`
- ZIPファイル

これにより、本番のパスワード設定、既存記事、管理画面から追加した画像を保護します。
