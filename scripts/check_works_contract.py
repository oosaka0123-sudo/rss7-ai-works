#!/usr/bin/env python3
"""Validate the WORKS page's SEO and mobile-navigation contract."""

from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
path = ROOT / "works.html"
text = path.read_text(encoding="utf-8")

required = {
    "canonical": '<link rel="canonical" href="https://rss7.net/works.html">',
    "robots": '<meta name="robots" content="index, follow">',
    "og:url": '<meta property="og:url" content="https://rss7.net/works.html">',
    "menu aria-controls": 'aria-controls="mobileMenu"',
    "mobile menu id": 'id="mobileMenu"',
    "contact CTA": 'href="contact.html"',
}

missing = [name for name, marker in required.items() if marker not in text]
if missing:
    raise SystemExit("works.html contract failed: " + ", ".join(missing))

print("works.html contract OK")
