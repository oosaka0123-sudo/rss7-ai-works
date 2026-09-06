#!/usr/bin/env python3
"""Validate the contract between service CTAs and the contact service select."""

from __future__ import annotations

import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import parse_qs, urlparse

ROOT = Path(__file__).resolve().parents[1]


class ServicesParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.consult_hrefs: list[str] = []
        self.service_ids: list[str] = []

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        values = dict(attrs)
        classes = set((values.get("class") or "").split())
        if tag == "a" and "service-consult" in classes:
            self.consult_hrefs.append(values.get("href") or "")
        if "service-detail" in classes and values.get("id"):
            self.service_ids.append(values["id"] or "")


class ContactParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.in_service_select = False
        self.in_option = False
        self.option_value: str | None = None
        self.option_text: list[str] = []
        self.services: list[str] = []

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        values = dict(attrs)
        if tag == "select" and values.get("id") == "service":
            self.in_service_select = True
        elif tag == "option" and self.in_service_select:
            self.in_option = True
            self.option_value = values.get("value")
            self.option_text = []

    def handle_data(self, data: str) -> None:
        if self.in_option:
            self.option_text.append(data)

    def handle_endtag(self, tag: str) -> None:
        if tag == "option" and self.in_option:
            text = "".join(self.option_text).strip()
            value = self.option_value if self.option_value is not None else text
            if value:
                self.services.append(value)
            self.in_option = False
            self.option_value = None
            self.option_text = []
        elif tag == "select" and self.in_service_select:
            self.in_service_select = False


def main() -> int:
    errors: list[str] = []

    services_parser = ServicesParser()
    services_parser.feed((ROOT / "services.html").read_text(encoding="utf-8"))
    contact_parser = ContactParser()
    contact_parser.feed((ROOT / "contact.html").read_text(encoding="utf-8"))

    if not services_parser.consult_hrefs:
        errors.append("services.html: service-consult linkがありません")
    if len(services_parser.service_ids) != len(set(services_parser.service_ids)):
        errors.append("services.html: service-detail idが重複しています")

    allowed = set(contact_parser.services)
    if not allowed:
        errors.append("contact.html: #service の選択肢を取得できません")

    linked_services: list[str] = []
    for href in services_parser.consult_hrefs:
        parsed = urlparse(href)
        if parsed.path != "contact.html":
            errors.append(f"services.html: service-consult の遷移先が不正です: {href}")
            continue
        values = parse_qs(parsed.query, keep_blank_values=True).get("service", [])
        if len(values) != 1 or not values[0]:
            errors.append(f"services.html: service queryが不正です: {href}")
            continue
        service = values[0]
        linked_services.append(service)
        if service not in allowed:
            errors.append(f"services.html: contact.htmlに存在しないservice値です: {service}")

    if len(linked_services) != len(set(linked_services)):
        errors.append("services.html: service-consult のservice値が重複しています")

    if errors:
        for message in errors:
            print(f"ERROR: {message}", file=sys.stderr)
        print(f"FAILED: service/contact contract {len(errors)}件", file=sys.stderr)
        return 1

    print(
        f"OK: service/contact contract validated "
        f"({len(services_parser.consult_hrefs)} consultation links, "
        f"{len(services_parser.service_ids)} service anchors)"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
