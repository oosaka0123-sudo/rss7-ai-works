# Secrets Management

## 原則

このリポジトリには秘密情報の実値を書かない。

禁止例:

- APIキー
- パスワード
- FTP認証情報
- 秘密鍵
- OAuth client secret
- セッション秘密鍵
- 本番メール送信認証情報

## 保存場所

秘密情報は用途に応じて次のどちらかに保存する。

1. GitHub Actionsで必要: GitHub Actions Secrets
2. ロリポップ本番でのみ必要: サーバー環境変数またはサーバー側の非公開設定

## 所在台帳の書き方

実値ではなく「名前・用途・保存場所」だけをこの文書に追記する。

例:

| Name | Purpose | Location | Value in repo |
|---|---|---|---|
| `JULES_API_KEY` | 将来のJules API自動起動 | GitHub Actions Secrets | 絶対に記載しない |
| FTP credential | 将来の限定デプロイ | GitHub Actions Secrets | 絶対に記載しない |

## Jules API

Jules APIをGitHub Actionsから呼ぶ場合、APIキーは `JULES_API_KEY` などのGitHub Actions Secretとして設定し、WorkflowやIssue本文へ直接書かない。
