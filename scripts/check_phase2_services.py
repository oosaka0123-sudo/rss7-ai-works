from pathlib import Path

html = Path('services.html').read_text(encoding='utf-8')

required = [
    'href="works.html" class="nav-link"',
    'id="business-services"',
    '法人・店舗向け 制作／開発',
    'id="creative-services"',
    '運用・クリエイティブ支援',
    'href="#business-services"',
    'href="#creative-services"',
    '--gray:#7898b8',
    'prefers-reduced-motion:reduce',
    'env(safe-area-inset-bottom)',
]

missing = [item for item in required if item not in html]
if missing:
    raise SystemExit('Phase 2 SERVICES contract missing: ' + ', '.join(missing))

service_ids = [
    'web-development', 'app-development', 'ai-video', 'ai-consulting',
    'memory-video', 'sns-management', 'ai-music', 'line-newsletter',
]
for service_id in service_ids:
    if html.count(f'id="{service_id}"') != 1:
        raise SystemExit(f'Expected exactly one service id: {service_id}')

if '従来の制作費の数分の一' in html or '全業種対応' in html:
    raise SystemExit('Unqualified AI video claims remain in services.html')

print('Phase 2 SERVICES contract: OK')
