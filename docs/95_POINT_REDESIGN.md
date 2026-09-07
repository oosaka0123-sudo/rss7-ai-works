# RSS7 AI Works — 95点リデザイン設計

Status: Phase 1 implementation baseline

## 固定デザイン制約

- 背景は現行の黒 `#020408` を維持する。
- シアン / ブルーを主要アクセントとして維持する。
- 現行CSSを基本資産として再利用し、別テーマへの全面置換をしない。
- Performance First / Content First / Usability First / Motion Second / AI Backstage。
- 初期表示をアニメーション・API・AI処理・動画待ちでブロックしない。
- JSが失敗しても本文・リンク・ナビゲーション・CTAを読める構造を優先する。

## STEP A — Wireframe

### TOP

1. Header
   - TOP / SERVICES / WORKS / BLOG / ABOUT / CONTACT
   - 外部ブランドは主ナビから外し、WORKS側で整理する。
2. Hero
   - 何を頼めるか: AI × Web / Video / App
   - 主CTA: 無料相談
   - 副CTA: 制作実績を見る
3. Trust strip
   - 大阪拠点 / 全国対応 / 無料相談 / 人が最終品質確認
   - 確認済み事実のみ。
4. Primary services
   - WEB制作 / アプリ開発 / AI動画CM / AI導入支援
5. Case studies
   - Claude Code教室
   - AI開発サイト
   - 如願寺 7ページ制作デモ
6. Why RSS7
   - AI制作
   - 別AIレビュー
   - 人が最終判断
7. Other services
   - SNS運用 / 自分史 / AI楽曲 / LINE・メルマガ
8. Process
9. Insights / Blog
10. Final CTA

### WORKS

- 制作物を「顧客実績」と偽装しない。
- `制作サイト / 制作デモ / 研究・開発 / 運営ブランド` を明確に分類する。
- 各案件は、名称・種別・目的・確認可能な特徴・実サイトリンクを持つ。
- 未確認の売上、PV、顧客評価、成果数値は記載しない。

### SERVICES

- 法人・店舗向け主力領域を先に配置。
- 個人向け・運用支援系を第二グループへ。
- 8サービス自体は削除しない。

## STEP B — Interactive Prototype

### Motion rules

- Hero: 初期HTMLを即表示。タイトルの軽い opacity/translate のみ。
- Section reveal: IntersectionObserverで1回のみ。失敗時は内容を隠さないフォールバックを持つ。
- Hover: border / underline / small translate を中心とし、レイアウトを揺らさない。
- Works: hover時に外部リンク方向を示す。画像がない案件でも内容理解を妨げない。
- Parallax / scroll-jack / 3D / 大量GSAPはPhase 1では使用しない。
- `prefers-reduced-motion: reduce` では移動アニメーションを停止する。

## STEP C — Frontend Design

- Modern / Editorial / Premium / Clean / Bold。
- 黒背景と既存シアンを維持。
- Orbitronはブランドラベル・短い見出しに限定し、日本語本文を主役にする。
- 本文は現行より明るい `#7898b8` を最低基準とする。
- 390pxでは1画面1メッセージを意識し、カード密度を下げる。
- タップ対象は概ね44–48px以上を確保。
- 固定LINEボタン用にモバイル下端 safe area を確保する。

## TONMANAから参考にする設計思想（コピー禁止）

- 大きなタイポグラフィと余白で情報の優先順位を明確化。
- サービス説明だけで終わらず、実績を早い段階で提示する。
- セクションごとに情報密度とリズムを変える。
- 最終的な問い合わせ導線をページ全体で一貫させる。
- 配色・形・コピー・レイアウトはRSS7独自の黒×シアンへ置き換える。

## Phase 1

1. `works.html` を追加。
2. TOPの副CTAをWORKSへ変更。
3. TOPの既存サイト紹介を `CASE STUDIES / 制作実績・プロジェクト` として再整理。
4. WORKSをサイトマップへ追加。
5. 次PRで共通ナビとSERVICES分類を横断反映。

## QA gate

- HTML / JS / PHP既存CI成功
- sitemap freshness成功
- 既存contact / blog / service-contact contractを壊さない
- 320 / 360 / 375 / 390 / 430pxで横はみ出しを発生させない
- reduced motion対応
- WORKS外部リンクが正しい
- 未確認情報を追加しない
