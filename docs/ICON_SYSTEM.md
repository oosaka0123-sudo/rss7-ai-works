# RSS7 AI Works — Icon System

## Direction

- Emoji / OS依存アイコンはサービスUIに使用しない。
- 黒背景 + シアン / ブルーに合う細線のtechnical SVGで統一する。
- 外部アイコンライブラリや追加フォントは使わず、inline SVGを基本とする。
- 32–34px前後、`stroke-width: 1.35` を基準にし、角丸・強い発光・多色塗りを避ける。
- hoverは小さなtranslateとcyanへのstroke変化まで。意味のない回転や常時アニメーションは禁止。
- SVGは装飾扱いの場合 `aria-hidden="true"` とし、サービス名をアクセシブルネームの主体にする。

## Current coverage

- TOP: 8サービス + AI TEAM
- SERVICES: 8サービス詳細

## Performance

SVGはHTML内に直接配置し、追加HTTPリクエスト・JS・アイコンライブラリを発生させない。
