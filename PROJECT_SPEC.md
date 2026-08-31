# RSS7 AI Works プロジェクト仕様

## 最終目標

RSS7 AI Worksと関連サイトを、低リスクな日常更新については人間が操作しなくても安全に更新できる状態にする。

目標フロー:

1. 定期トリガーまたは短い指示からGitHub Issueを作る
2. ChatGPT/ルールがリスクを分類する
3. 低リスクはJulesを第一実装担当として自動起動する
4. Julesが実装・自己確認・PR作成を行う
5. GitHub Copilotが独立レビューする
6. GitHub Actionsが品質・安全性を機械検査する
7. 低リスクかつ全検査合格のみ自動マージ対象にする
8. 自動デプロイ後に本番を検証する
9. 異常時は安全条件を満たす範囲で自動ロールバックする
10. 高リスク変更と自動復旧不能時だけ人間へエスカレーションする

## AIの役割

- ChatGPT: PM/司令塔、タスク分類、Issue管理、例外判断
- Jules: 日常の簡単・定型・低リスク更新の第一実装担当
- GitHub Copilot: PRレビュー
- GitHub Actions: テスト、安全ゲート、状態同期、将来の自動デプロイ/監視
- Claude Code: Julesで解決困難な複雑実装・大規模変更・難しいデバッグ

## 状態管理

GitHubをSSOTとする。

- Task Queue: GitHub Issues
- Work Unit: Pull Requests
- Confirmed Code: `main`
- `PROJECT_STATE.json`: Issue/PR/mainから再生成可能な軽量スナップショット
- `STATUS.md`: `PROJECT_STATE.json` から生成する人間向け表示
- `DECISIONS.md`: 重要な設計判断だけを日付単位で保存

`PROJECT_STATE.json` と `STATUS.md` は初期導入後、GitHub Actionsだけが更新する。

## 現在の公開構成

- 公開用HTML
- 公開ブログ記事
- 公開画像
- `data/articles.json`
- `robots.txt` と `sitemap.xml`
- `api/` のブログ管理API（認証・記事・アップロード処理を含む）

## 公開環境

現行サイトはロリポップ側で稼働している。GitHub PagesだけではPHP、認証API、自動投稿処理は実行できない。

## 重要な制約

- このリポジトリはサーバー全体の完全バックアップではない。
- 本番ファイルを一括削除・一括置換しない。
- API、自動投稿、FTPなどの秘密情報はGitHub Secretsまたはサーバー環境変数で管理する。
- 受領ZIP内の旧メールフォーム、ログ、バックアップ、未監査PHP、サーバー設定、内包ZIPは公開しない。
- `.htaccess`、DB、認証、決済、課金、Secrets、ドメイン、デプロイ方式変更は人間承認必須とする。
- `api/` は高リスク領域として、Julesの低リスク自動更新対象から除外する。

## Phase 1

Phase 1は安全なJules-first自動実装基盤を作る。

- Issueテンプレート
- `agent:jules` を標準担当にする
- Jules API自動起動
- Julesの自動PR作成
- Copilotレビュー指示
- quality / safety checks
- 同時実装1件制限
- タイムアウト監視
- 最大3回の再試行上限
- Actions専用の状態更新
- Secrets所在方針
- Rollback手順

自動マージ、自動デプロイ、本番検証、自動ロールバックは、Phase 1の安全ゲートを実運用で確認した後に順次有効化する。

## 現在の残課題

- JulesのGitHub連携でこのリポジトリを許可する
- GitHub Actions Secret `JULES_API_KEY` を設定する
- mainの保護ルールと必須チェックを有効化する
- repository auto-mergeを安全ゲート確認後に有効化する
- ロリポップへの限定自動デプロイ方式を設計する
- 本番ヘルスチェックと自動ロールバック条件を実装する
