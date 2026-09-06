# Production deploy variable note

`production` の Environment variable として設定した値は、job-level `if:` の評価時点では参照できないため、自動デプロイの有効化フラグを job-level `if:` に置かない。

本番デプロイは以下の既存安全条件で制御する。

- Site quality が success
- 元イベントが push
- 対象 branch が main
- production Environment を使用
- FTP/FTPS 接続情報の完全性を実行時検証
- `/` へのデプロイは `https://rss7.net` のみ許可
- サーバー専用設定と公開データを除外
- デプロイ後に contact/API/403 を検証

手動デプロイは `workflow_dispatch` と `validate-manual` を使用する。
