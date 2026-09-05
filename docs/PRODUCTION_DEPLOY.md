# ロリポップ本番デプロイ

`Deploy production` は、mainの内容だけをロリポップ本番へ反映します。

- 初回や任意の本番反映は、Actions画面から `Run workflow` を手動実行します。手動実行では、同じWorkflow内で品質検査に合格した場合だけ転送します。
- mainの `Site quality` 成功後に自動デプロイしたい場合だけ、Repository variable `LOLIPOP_AUTO_DEPLOY_ENABLED=true` を別途設定します。未設定または `false` の間は自動デプロイしません。

## GitHub Environment `production` に登録する値

Secrets（値を画面やチャットへ貼らない）:

- `LOLIPOP_FTP_HOST`
- `LOLIPOP_FTP_USER`
- `LOLIPOP_FTP_PASSWORD`

Variables:

- `LOLIPOP_FTP_SCHEME`: explicit FTPSは `ftp`、implicit FTPSは `ftps`
- `LOLIPOP_FTP_PORT`: ロリポップ管理画面に表示されたポート
- `LOLIPOP_DEPLOY_DIR`: `rss7.net` はロリポップの公開（アップロード）フォルダが空欄であることを確認済みのため `/`
- `LOLIPOP_SITE_URL`: `https://rss7.net`

`LOLIPOP_DEPLOY_DIR=/` は、`LOLIPOP_SITE_URL=https://rss7.net` の組み合わせでだけ許可します。他ドメインや未確認の公開先へルートデプロイしてはいけません。

## 手動デプロイ

1. `api/config.example.php` を参考に、本番だけに `api/config.local.php` を作る。過去の平文パスワードは再利用しない。
2. `private_data_dir` は、すべての `public_html` / DocumentRoot の外側に設定する。
3. PHP 7.4以上、`mbstring`、`fileinfo`、`DOM` が利用できることを確認する。
4. `images/blog/` と `private_data_dir` をPHPから書き込めるようにする。
5. `https://rss7.net/data/articles.json` がHTTP 403になることを確認する。
6. ロリポップ側の現行 `rss7.net` をバックアップし、公開先ディレクトリと `LOLIPOP_SITE_URL` を再確認する。
7. GitHub Actionsの `Deploy production` を開き、mainを選んで `Run workflow` を実行する。
8. 手動実行内の品質検査に合格した場合だけFTP転送へ進む。

手動実行そのものが明示的な本番承認になるため、同名のRepository variableとEnvironment variableを二重登録する必要はありません。

## 自動デプロイを有効化する場合

`Settings -> Secrets and variables -> Actions -> Variables` のRepository variablesに、次を追加します。

- `LOLIPOP_AUTO_DEPLOY_ENABLED`: `true`

この変数はEnvironment variableではありません。mainへのpushで `Site quality` が成功した場合だけ自動デプロイを許可するジョブ開始前の安全ゲートです。

自動デプロイを止める場合は `LOLIPOP_AUTO_DEPLOY_ENABLED=false` にするか削除します。

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
