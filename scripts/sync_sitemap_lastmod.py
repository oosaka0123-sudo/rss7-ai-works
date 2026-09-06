#!/usr/bin/env python3
"""Synchronize sitemap.xml lastmod values with Git history.

The latest author timestamp for each source file is converted to Asia/Tokyo and
used as the page freshness date. In pull requests, CI passes the actual head
commit through SITEMAP_GIT_REF so GitHub's synthetic merge commit cannot create
false freshness changes. The blog index also considers data/articles.json
because its rendered content depends on it.

Usage:
  python scripts/sync_sitemap_lastmod.py          # rewrite sitemap.xml
  python scripts/sync_sitemap_lastmod.py --check  # fail when out of sync
"""

from __future__ import annotations

import argparse
import os
import subprocess
import sys
import xml.etree.ElementTree as ET
from datetime import datetime
from pathlib import Path
from urllib.parse import unquote, urlparse
from zoneinfo import ZoneInfo

ROOT = Path(__file__).resolve().parents[1]
SITEMAP = ROOT / "sitemap.xml"
NS = "http://www.sitemaps.org/schemas/sitemap/0.9"
GIT_REF = os.environ.get("SITEMAP_GIT_REF", "HEAD")
JST = ZoneInfo("Asia/Tokyo")
ET.register_namespace("", NS)


def git_date(path: Path) -> str:
    rel = path.relative_to(ROOT).as_posix()
    result = subprocess.run(
        ["git", "log", "-1", "--format=%aI", GIT_REF, "--", rel],
        cwd=ROOT,
        text=True,
        capture_output=True,
        check=True,
    )
    value = result.stdout.strip()
    if not value:
        raise RuntimeError(f"Git history timestamp not found: {rel} at {GIT_REF}")
    try:
        return datetime.fromisoformat(value).astimezone(JST).date().isoformat()
    except ValueError as exc:
        raise RuntimeError(f"Invalid Git author timestamp for {rel}: {value}") from exc


def source_paths_for_url(url: str) -> list[Path]:
    parsed = urlparse(url)
    relative = unquote(parsed.path.lstrip("/")) or "index.html"
    paths = [ROOT / relative]
    if relative == "blog.html":
        paths.append(ROOT / "data/articles.json")
    return paths


def expected_lastmod(url: str) -> str:
    paths = source_paths_for_url(url)
    for path in paths:
        if not path.is_file():
            raise RuntimeError(f"Sitemap source file does not exist: {path.relative_to(ROOT)}")
    return max(git_date(path) for path in paths)


def write_sitemap(root: ET.Element) -> None:
    body = ET.tostring(root, encoding="unicode", short_empty_elements=True)
    SITEMAP.write_text(
        '<?xml version="1.0" encoding="UTF-8"?>\n' + body + '\n',
        encoding="utf-8",
    )


def synchronize(check_only: bool) -> int:
    tree = ET.parse(SITEMAP)
    root = tree.getroot()
    mismatches: list[tuple[str, str, str]] = []

    for url_node in root.findall(f"{{{NS}}}url"):
        loc_node = url_node.find(f"{{{NS}}}loc")
        lastmod_node = url_node.find(f"{{{NS}}}lastmod")
        if loc_node is None or not (loc_node.text or "").strip():
            raise RuntimeError("sitemap.xml contains a URL entry without loc")
        url = (loc_node.text or "").strip()
        expected = expected_lastmod(url)
        current = (lastmod_node.text or "").strip() if lastmod_node is not None else ""
        if current != expected:
            mismatches.append((url, current, expected))
            if not check_only:
                if lastmod_node is None:
                    lastmod_node = ET.SubElement(url_node, f"{{{NS}}}lastmod")
                lastmod_node.text = expected

    if check_only:
        if mismatches:
            for url, current, expected in mismatches:
                print(f"OUTDATED: {url} lastmod={current or '(missing)'} expected={expected}", file=sys.stderr)
            print(f"FAILED: sitemap lastmod mismatch {len(mismatches)}件", file=sys.stderr)
            return 1
        print(f"OK: sitemap lastmod is synchronized with Git author history at {GIT_REF}")
        return 0

    write_sitemap(root)
    if mismatches:
        print(f"UPDATED: sitemap.xml lastmod {len(mismatches)}件")
    else:
        print("OK: sitemap.xml already synchronized; formatting normalized")
    return 0


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--check", action="store_true", help="check only; do not rewrite sitemap.xml")
    args = parser.parse_args()
    try:
        return synchronize(args.check)
    except (OSError, RuntimeError, subprocess.CalledProcessError, ET.ParseError) as exc:
        print(f"ERROR: {exc}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
