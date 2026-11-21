#!/usr/bin/env python3
"""
Content Guardian — Read-Only WordPress & WooCommerce Exposure CLI Auditor
Zero external dependencies (Python 3 stdlib only).

Checks four public read-only endpoints using HTTP GET:
  1. /wp-json/wp/v2/posts?per_page=3
  2. /wp-json/wc/store/v1/products?per_page=3
  3. /feed/
  4. /robots.txt (+ origin TDM-Reservation header)

Exit codes:
  0 = low exposure (no exposed JSON/RSS/AI bot surfaces detected)
  2 = one or more scraping surfaces are publicly exposed
  1 = invalid arguments or network failure
"""
import argparse
import json
import re
import sys
import urllib.error
import urllib.parse
import urllib.request

MAX_BYTES = 98304
AI_BOTS = ("GPTBot", "ClaudeBot", "CCBot", "Google-Extended", "Bytespider")
UA = "ContentGuardian-CLI/1.0 (+https://gexiro.com/en/tools/wordpress-exposure-check)"


def normalize_origin(raw: str) -> str:
    candidate = raw.strip()
    if not candidate:
        raise ValueError("Domain or URL must not be empty.")
    if not candidate.startswith(("http://", "https://")):
        candidate = "https://" + candidate
    parsed = urllib.parse.urlsplit(candidate)
    if parsed.scheme not in ("http", "https") or not parsed.netloc:
        raise ValueError(f"Invalid URL: {raw}")
    return f"{parsed.scheme}://{parsed.netloc}"


def fetch(url: str, timeout: float = 6.0):
    req = urllib.request.Request(url, method="GET", headers={"User-Agent": UA})
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            body = resp.read(MAX_BYTES).decode("utf-8", errors="replace")
            headers = {k.lower(): v for k, v in resp.headers.items()}
            return resp.status, headers, body
    except urllib.error.HTTPError as exc:
        body = exc.read(MAX_BYTES).decode("utf-8", errors="replace")
        headers = {k.lower(): v for k, v in exc.headers.items()} if exc.headers else {}
        return exc.code, headers, body


def parse_ai_robots(robots_txt: str):
    groups = {}
    current = None
    for raw_line in robots_txt.splitlines():
        line = raw_line.split("#", 1)[0].strip()
        if not line or ":" not in line:
            continue
        field, val = (p.strip() for p in line.split(":", 1))
        low = field.lower()
        if low == "user-agent":
            current = val.lower()
            groups.setdefault(current, [])
        elif low == "disallow" and current is not None:
            groups[current].append(val)

    wildcard_blocked = "/" in groups.get("*", [])
    blocked, allowed = [], []
    for bot in AI_BOTS:
        rules = groups.get(bot.lower())
        is_blocked = ("/" in rules) if rules is not None else wildcard_blocked
        (blocked if is_blocked else allowed).append(bot)
    return blocked, allowed


def audit_site(target: str) -> dict:
    origin = normalize_origin(target)
    exposed_surfaces = 0

    # 1. WordPress REST API posts
    status, headers, body = fetch(f"{origin}/wp-json/wp/v2/posts?per_page=3")
    rest = {"status": "NOT_DETECTED", "http_status": status, "total_posts": None, "sample_titles": []}
    if status == 200 and body.lstrip().startswith("["):
        try:
            items = json.loads(body)
            if isinstance(items, list):
                total_hdr = headers.get("x-wp-total")
                rest["total_posts"] = int(total_hdr) if total_hdr and total_hdr.isdigit() else len(items)
                has_full_body = any(isinstance(i, dict) and len(((i.get("content") or {}).get("rendered") or "")) > 240 for i in items)
                rest["sample_titles"] = [
                    re.sub(r"<[^>]+>", "", ((i.get("title") or {}).get("rendered") or "")).strip()[:80]
                    for i in items[:2] if isinstance(i, dict)
                ]
                rest["status"] = "EXPOSED" if (has_full_body and items) else "PROTECTED"
                if rest["status"] == "EXPOSED":
                    exposed_surfaces += 1
        except json.JSONDecodeError:
            pass
    elif status in (401, 403):
        rest["status"] = "PROTECTED"

    # 2. WooCommerce Store API
    w_status, w_headers, w_body = fetch(f"{origin}/wp-json/wc/store/v1/products?per_page=3")
    woo = {"status": "NOT_DETECTED", "http_status": w_status, "total_products": None}
    if w_status == 200 and w_body.lstrip().startswith("["):
        try:
            items = json.loads(w_body)
            if isinstance(items, list) and items:
                total_hdr = w_headers.get("x-wp-total")
                woo["total_products"] = int(total_hdr) if total_hdr and total_hdr.isdigit() else len(items)
                woo["status"] = "EXPOSED"
                exposed_surfaces += 1
        except json.JSONDecodeError:
            pass
    elif w_status in (401, 403):
        woo["status"] = "PROTECTED"

    # 3. RSS Feed + TDM Header
    f_status, f_headers, f_body = fetch(f"{origin}/feed/")
    tdm_present = headers.get("tdm-reservation") == "1" or f_headers.get("tdm-reservation") == "1"
    rss = {"status": "NOT_DETECTED", "http_status": f_status}
    if f_status == 200 and ("<rss" in f_body or "<feed" in f_body):
        full_rss = "<content:encoded>" in f_body
        rss["status"] = "EXPOSED" if full_rss else "PROTECTED"
        if full_rss:
            exposed_surfaces += 1
    elif f_status in (401, 403, 429):
        rss["status"] = "PROTECTED"

    # 4. robots.txt AI Crawlers
    r_status, _, r_body = fetch(f"{origin}/robots.txt")
    robots_found = r_status == 200 and ("user-agent" in r_body.lower() or "sitemap" in r_body.lower())
    blocked_bots, allowed_bots = parse_ai_robots(r_body) if robots_found else ([], list(AI_BOTS))
    ai_status = "EXPOSED" if len(allowed_bots) == len(AI_BOTS) else ("PARTIAL" if allowed_bots else "PROTECTED")
    if ai_status != "PROTECTED":
        exposed_surfaces += 1

    return {
        "origin": origin,
        "exposed_surfaces": exposed_surfaces,
        "tdm_reservation_header": tdm_present,
        "rest_posts": rest,
        "woo_store": woo,
        "rss_feed": rss,
        "ai_robots": {"status": ai_status, "robots_found": robots_found, "blocked": blocked_bots, "allowed": allowed_bots},
    }


def main() -> int:
    parser = argparse.ArgumentParser(description="Content Guardian read-only WordPress/WooCommerce exposure check")
    parser.add_argument("url", help="Target domain or https:// URL")
    parser.add_argument("--json", action="store_true", help="Output raw JSON report")
    args = parser.parse_args()

    try:
        report = audit_site(args.url)
    except Exception as exc:
        print(f"Error: {exc}", file=sys.stderr)
        return 1

    if args.json:
        print(json.dumps(report, indent=2))
    else:
        print(f"Target: {report['origin']} | Exposed surfaces: {report['exposed_surfaces']}/4 | TDM-Reservation: {report['tdm_reservation_header']}")
        print(f"  REST /wp/v2/posts          : {report['rest_posts']['status']} (HTTP {report['rest_posts']['http_status']}, total={report['rest_posts']['total_posts']})")
        print(f"  WooCommerce Store API      : {report['woo_store']['status']} (HTTP {report['woo_store']['http_status']}, total={report['woo_store']['total_products']})")
        print(f"  RSS /feed/                 : {report['rss_feed']['status']} (HTTP {report['rss_feed']['http_status']})")
        print(f"  AI Crawlers (/robots.txt)  : {report['ai_robots']['status']} (allowed={report['ai_robots']['allowed']})")

    return 2 if report["exposed_surfaces"] > 0 else 0


if __name__ == "__main__":
    raise SystemExit(main())
