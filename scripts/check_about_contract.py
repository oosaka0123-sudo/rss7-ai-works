from pathlib import Path

html = Path('about.html').read_text(encoding='utf-8')

required = [
    'href="works.html"',
    '制作サイト・制作デモ・研究開発をWORKSで見る',
    '--gray:#7898b8',
    'prefers-reduced-motion:reduce',
    'env(safe-area-inset-bottom)',
    'AIは舞台裏。最終品質は人が確認します。',
    'class="skill-icon"><svg',
    'aria-controls="mobileMenu"',
    'id="mobileMenu"',
]
missing = [item for item in required if item not in html]
if missing:
    raise SystemExit('ABOUT contract missing: ' + ', '.join(missing))

for emoji in ['🤖', '🎨', '🎬', '🌐', '📱', '🎵']:
    if emoji in html:
        raise SystemExit(f'Emoji icon returned to ABOUT: {emoji}')

if '低コスト・短納期・4K品質' in html:
    raise SystemExit('Unqualified BitFrame claim returned to ABOUT')

print('ABOUT trust/icon contract: OK')
