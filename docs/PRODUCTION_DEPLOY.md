# ロリポップ本番デプロイ

`Deploy production` は、mainの内容だけをロリポップ本番へ反映します。

- 初回や任意の本番反映は、Actions画面から `Run workflow` を手動実行します。手動実行では、同じWorkflow内で品質検査に合格した場合だけ転送します。
- mainの `Site quality` 成功後に自動デプロイしたい場合だけ、Repository variable `LOLIPOP_AUTO_DEPLOY_ENABLED=true` を別途設定します。未設定または `false` の間は自動デプロイしません。

## GitHub Environment `production` に登録する値

Secrets（値を画面やチャットへ貼らない）:

- `LOLIPOP_FTP_HOST`
- `LOLIPOP_FTP_USER`
- `LOLIPOP_FTP_PASSWORD`
- `RSS7_ADMIN_PASSWORD` — `api/config.local.php` が本番に存在しない初回だけ使用する新しい管理画面パスワード。12文字以上。過去の平文パスワードは再利用しない。

Variables:

- `LOLIPOP_FTP_SCHEME`: explicit FTPSは `ftp`、implicit FTPSは `ftps`
- `LOLIPOP_FTP_PORT`: ロリポップ管理画面に表示されたポート
- `LOLIPOP_DEPLOY_DIR`: `rss7.net` はロリポップの公開（アップロード）フォルダが空欄であることを確認済みのため `/`
- `LOLIPOP_SITE_URL`: `https://rss7.net`

`LOLIPOP_DEPLOY_DIR=/` は、`LOLIPOP_SITE_URL=https://rss7.net` の組み合わせでだけ許可します。他ドメインや未確認の公開先へルートデプロイしてはいけません。

## 本番API設定の初回ブートストラップ

`api/config.local.php` はGitHubへコミットせず、本番サーバーだけに保持します。

`Deploy production` はFTPで `api/` を確認し、既存の `config.local.php` があれば一切上書きせず、そのまま保護します。存在しない場合だけ `production` Environment secret `RSS7_ADMIN_PASSWORD` を使って一時ファイルを生成し、本番の `api/config.local.php` として1回だけ配置します。

生成時の安全方針:

- 管理パスワードはログへ表示せず、PHP `password_hash()` の結果だけを本番設定へ保存する。
- `contact_rate_secret` は64文字のランダム値をその場で新規生成する。
- `private_data_dir` は `dirname($_SERVER['DOCUMENT_ROOT']) . '/rss7-private'` とし、DocumentRootの外側を使う。
- `contact_recipient` / `contact_sender` は `info@rss7.net` を使う。
- 生成した一時ファイルはアップロード後にRunnerから削除する。
- FTPで `api/` を正常に確認できない場合は、既存設定を誤って上書きしないよう失敗終了する。

`RSS7_ADMIN_PASSWORD` が未登録で、かつ本番に `config.local.php` が無い場合は、サイト本体のアップロード前に安全停止します。

## 手動デプロイ

1. `production` Environment に上記Secrets / Variablesを設定する。`RSS7_ADMIN_PASSWORD` は新しい12文字以上の値を使い、チャットやIssueへ貼らない。
2. PHP 7.4以上、`mbstring`、`fileinfo`、`DOM` が利用できることを確認する。
3. `images/blog/` と、本番で生成される公開領域外の `rss7-private` をPHPから書き込める必要がある。
4. ロリポップ側の現行 `rss7.net` をバックアップし、公開先ディレクトリと `LOLIPOP_SITE_URL` を再確認する。
5. GitHub Actionsの `Deploy production` を開き、mainを選んで `Run workflow` を実行する。
6. 手動実行内の品質検査に合格した場合だけFTP転送へ進む。
7. 初回は `config.local.php` の存在確認/必要時ブートストラップ後にサイト本体を転送する。
8. 公開後の自動確認で、ページ/API/保護ファイルがすべて正常になることを確認する。

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
