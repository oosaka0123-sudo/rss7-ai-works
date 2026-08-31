#!/usr/bin/env python3
"""RSS7 AI Worksの公開前に、構文・リンク・SEO・危険ファイルを検査する。"""

from __future__ import annotations

import json
import re
import struct
import sys
import xml.etree.ElementTree as ET
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import unquote, urlparse

ROOT = Path(__file__).resolve().parents[1]
ERRORS: list[str] = []
WARNINGS: list[str] = []
RUNTIME_ENDPOINTS: set[str] = set()
MAIN_PAGES = ["index.html", "services.html", "about.html", "contact.html", "blog.html", "privacy.html"]


class ReferenceParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.references: list[str] = []

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        values = dict(attrs)
        for key in ("href", "src"):
            if values.get(key):
                self.references.append(values[key] or "")


def error(message: str) -> None:
    ERRORS.append(message)


def jpeg_size(path: Path) -> tuple[int, int]:
    with path.open("rb") as file:
        if file.read(2) != b"\xff\xd8":
            raise ValueError("JPEG magicがありません")
        while True:
            marker_start = file.read(1)
            if not marker_start:
                raise ValueError("JPEGサイズを取得できません")
            if marker_start != b"\xff":
                continue
            marker = file.read(1)
            while marker == b"\xff":
                marker = file.read(1)
            if marker in {bytes([m]) for m in range(0xC0, 0xC4)} | {bytes([m]) for m in range(0xC5, 0xC8)} | {bytes([m]) for m in range(0xC9, 0xCC)} | {bytes([m]) for m in range(0xCD, 0xD0)}:
                length = struct.unpack(">H", file.read(2))[0]
                payload = file.read(length - 2)
                return struct.unpack(">HH", payload[1:5])[::-1]
            length_bytes = file.read(2)
            if len(length_bytes) != 2:
                raise ValueError("JPEGセグメントが壊れています")
            length = struct.unpack(">H", length_bytes)[0]
            file.seek(length - 2, 1)


def check_json() -> None:
    path = ROOT / "data/articles.json"
    try:
        data = json.loads(path.read_text(encoding="utf-8"))
    except Exception as exc:
        error(f"data/articles.json: {exc}")
        return
    articles = data if isinstance(data, list) else data.get("articles")
    if not isinstance(articles, list) or not articles:
        error("data/articles.json: articles配列が空です")
        return
    ids: set[str] = set()
    for index, article in enumerate(articles):
        for key in ("id", "title", "date", "status"):
            if key not in article:
                error(f"data/articles.json: {index}番目に{key}がありません")
        article_id = str(article.get("id", ""))
        if article_id in ids:
            error(f"data/articles.json: id {article_id} が重複しています")
        ids.add(article_id)
        if article.get("status") not in {"published", "draft"}:
            error(f"data/articles.json: id {article_id} のstatusが不正です")


def check_sitemap() -> None:
    path = ROOT / "sitemap.xml"
    try:
        tree = ET.parse(path)
    except Exception as exc:
        error(f"sitemap.xml: {exc}")
        return
    namespace = {"sm": "http://www.sitemaps.org/schemas/sitemap/0.9"}
    locations = [node.text or "" for node in tree.findall("sm:url/sm:loc", namespace)]
    if len(locations) != len(set(locations)):
        error("sitemap.xml: URLが重複しています")
    for location in locations:
        parsed = urlparse(location)
        if parsed.scheme != "https" or parsed.netloc != "rss7.net":
            error(f"sitemap.xml: 対象外URL {location}")
            continue
        relative = unquote(parsed.path.lstrip("/")) or "index.html"
        if not (ROOT / relative).is_file():
            error(f"sitemap.xml: ローカルにないURL {location}")
    for page in MAIN_PAGES:
        expected = "https://rss7.net/" if page == "index.html" else f"https://rss7.net/{page}"
        if expected not in locations:
            error(f"sitemap.xml: {expected} がありません")


def check_html() -> None:
    for path in ROOT.rglob("*.html"):
        relative_path = path.relative_to(ROOT)
        try:
            text = path.read_text(encoding="utf-8")
        except UnicodeDecodeError as exc:
            error(f"{relative_path}: UTF-8ではありません ({exc})")
            continue
        parser = ReferenceParser()
        parser.feed(text)
        for reference in parser.references:
            clean = unquote(reference.split("#", 1)[0].split("?", 1)[0].strip())
            if not clean or clean.startswith(("http://", "https://", "mailto:", "tel:", "data:", "javascript:")):
                continue
            target = ROOT / clean.lstrip("/") if clean.startswith("/") else path.parent / clean
            normalized = target.resolve()
            try:
                normalized.relative_to(ROOT.resolve())
            except ValueError:
                error(f"{relative_path}: ルート外参照 {reference}")
                continue
            target_rel = normalized.relative_to(ROOT.resolve()).as_posix()
            if target_rel in RUNTIME_ENDPOINTS:
                WARNINGS.append(f"{relative_path}: 本番専用API {target_rel}")
            elif not normalized.exists():
                error(f"{relative_path}: 存在しない参照 {reference}")
    for page in MAIN_PAGES:
        text = (ROOT / page).read_text(encoding="utf-8")
        canonical = "https://rss7.net/" if page == "index.html" else f"https://rss7.net/{page}"
        if f'<link rel="canonical" href="{canonical}"' not in text:
            error(f"{page}: canonicalが不一致です")
        if 'content="https://rss7.net/images/og-image.jpg"' not in text:
            error(f"{page}: og:imageがありません")


def check_files() -> None:
    forbidden_names = {"auto_post.php", "upload.php", "php.ini", "rss7-complete.zip"}
    allowed_exception_paths = {"api/upload.php"}
    forbidden_suffixes = {".zip", ".bak", ".log"}
    for path in ROOT.rglob("*"):
        if not path.is_file() or ".git" in path.parts:
            continue
        relative = path.relative_to(ROOT)
        if ((path.name in forbidden_names and relative.as_posix() not in allowed_exception_paths)
                or path.suffix.lower() in forbidden_suffixes):
            error(f"公開禁止ファイル: {relative}")
        if path.name == "config.local.php":
            error(f"秘密設定ファイルが登録されています: {relative}")
        if path.stat().st_size > 1_000_000:
            error(f"1MBを超えるファイル: {relative}")
    og_image = ROOT / "images/og-image.jpg"
    try:
        if jpeg_size(og_image) != (1200, 630):
            error("images/og-image.jpg: 1200x630ではありません")
    except Exception as exc:
        error(f"images/og-image.jpg: {exc}")


def check_secrets() -> None:
    patterns = [
        re.compile(r"define\(\s*['\"][A-Z0-9_]*(?:KEY|TOKEN|SECRET|PASSWORD)['\"]\s*,\s*['\"][^'\"]{8,}['\"]", re.I),
        re.compile(r"['\"]password['\"]\s*=>\s*['\"][^'\"]{4,}['\"]", re.I),
    ]
    for path in ROOT.rglob("*.php"):
        if ".git" in path.parts or path.name == "config.example.php":
            continue
        text = path.read_text(encoding="utf-8", errors="replace")
        if any(pattern.search(text) for pattern in patterns):
            error(f"秘密値の直書き疑い: {path.relative_to(ROOT)}")


def main() -> int:
    check_json()
    check_sitemap()
    check_html()
    check_files()
    check_secrets()
    for warning in sorted(set(WARNINGS)):
        print(f"WARNING: {warning}")
    if ERRORS:
        for message in ERRORS:
            print(f"ERROR: {message}", file=sys.stderr)
        print(f"FAILED: {len(ERRORS)}件の問題", file=sys.stderr)
        return 1
    print("OK: RSS7 AI Worksの公開前検査に合格しました")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
