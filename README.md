# Content Guardian — WordPress & WooCommerce Anti-Scraping Suite + Exposure CLI

<p>
  <a href="https://wordpress.org/plugins/gexiro-content-protection/"><img src="https://img.shields.io/badge/WordPress.org-v3.3.7-21759B?style=flat-square&logo=wordpress&logoColor=white" alt="WordPress.org v3.3.7"></a>
  <img src="https://img.shields.io/badge/PHP-7.4%20--%208.4-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 7.4 - 8.4">
  <img src="https://img.shields.io/badge/License-GPL--2.0--or--later-10B981?style=flat-square" alt="GPL-2.0-or-later">
</p>

**Content Guardian** (`gexiro-content-protection`) is a lightweight, zero-external-request WordPress & WooCommerce content protection plugin (`GPL-2.0-or-later`), paired here with a **zero-dependency Python 3 read-only CLI auditor** (`cli/wp_exposure_check.py`) to inspect public scraping leaks across:

1. `/wp-json/wp/v2/posts` (unauthenticated full-article JSON responses)
2. `/wp-json/wc/store/v1/products` (public WooCommerce product catalog, descriptions, pricing & gallery URLs)
3. `/feed/` (full-HTML `<content:encoded>` syndication feeds)
4. `/robots.txt` & `TDM-Reservation: 1` (`GPTBot`, `ClaudeBot`, `CCBot`, `Google-Extended`, `Bytespider`)

---

## Free Online Exposure Scanners (No Installation Required)

Test any public WordPress or WooCommerce site in 3 seconds across **English**, **Deutsch (DACH)**, and **Polski**:

| Specialized Diagnostic Tool | English | Deutsch | Polski |
| :--- | :--- | :--- | :--- |
| **Full 4-Surface Exposure Check** | [Open EN](https://gexiro.com/en/tools/wordpress-exposure-check) | [Open DE](https://gexiro.com/de/tools/wordpress-exposure-check) | [Open PL](https://gexiro.com/pl/tools/wordpress-exposure-check) |
| **WooCommerce Catalog Scraping Check** | [Open EN](https://gexiro.com/en/tools/woocommerce-scraping-check) | [Open DE](https://gexiro.com/de/tools/woocommerce-scraping-check) | [Open PL](https://gexiro.com/pl/tools/woocommerce-scraping-check) |
| **AI Crawler & robots.txt Opt-Out Check** | [Open EN](https://gexiro.com/en/tools/ai-crawler-robots-check) | [Open DE](https://gexiro.com/de/tools/ai-crawler-robots-check) | [Open PL](https://gexiro.com/pl/tools/ai-crawler-robots-check) |
| **WordPress RSS Feed Leak Check** | [Open EN](https://gexiro.com/en/tools/wordpress-rss-leak-check) | [Open DE](https://gexiro.com/de/tools/wordpress-rss-leak-check) | [Open PL](https://gexiro.com/pl/tools/wordpress-rss-leak-check) |

---

## Quick Start: Read-Only CLI Audit

Requires only Python 3.8+ standard library (zero `pip` packages):

```bash
python3 cli/wp_exposure_check.py https://your-wordpress-site.com
python3 cli/wp_exposure_check.py https://your-woocommerce-store.com --json
```

Exit codes: `0` = low exposure, `2` = open scraping surfaces detected, `1` = input/network error.

---

## WordPress Plugin Installation (`v3.3.7`)

Recommended (includes compiled `.mo` translation binaries for DE, PL, ES, FR, IT, JA, NL, PT-BR):

1. In WordPress Admin go to **Plugins -> Add New**, search **Gexiro Content Protection** ([WordPress.org Directory](https://wordpress.org/plugins/gexiro-content-protection/)), or download the official build: [content-guardian-free-latest.zip](https://gexiro.com/content-guardian/content-guardian-free-latest.zip?v=3.3.7).
2. Activate and open **Gexiro Content Protection -> Dashboard** to view your zero-external-request **Site Exposure Self-Audit**.

*(The `plugin/` folder in this repository mirrors the exact PHP/CSS/JS/PO `GPL-2.0-or-later` source released on WordPress.org SVN; compiled binary `.mo` catalogs are excluded from git text history and distributed in the WordPress.org ZIP.)*

---

## Technical Hardening Guides

* [WordPress Security & Anti-Scraping Guides Hub](https://gexiro.com/en/guides) ([DE](https://gexiro.com/de/guides) · [PL](https://gexiro.com/pl/guides))
* [How to Block AI Crawlers & Content Scrapers in WordPress](https://gexiro.com/en/guides/block-ai-crawlers-wordpress)
* [How to Stop WordPress REST API Scraping (/wp-json/wp/v2/posts Leak)](https://gexiro.com/en/guides/wordpress-rest-api-posts-leak)
* [How to Protect WooCommerce Product Catalogs Without Breaking Checkout](https://gexiro.com/en/guides/protect-woocommerce-products-from-scraping)
* [How to Stop WordPress RSS Feed Autoblog Copying](https://gexiro.com/en/guides/stop-wordpress-rss-content-scraping)

---

## Free Edition vs. Content Guardian Pro

| Capability | Free Edition (`GPL-2.0-or-later`, `$0`) | Content Guardian Pro (`$29.25/yr` w/ `LAUNCH25`) |
| :--- | :--- | :--- |
| Right-click, selection & clipboard shield (form/checkout-safe) | Included | Included + Anti-clone DOM shield |
| Zero-external-request local REST / Woo / RSS Exposure Self-Audit | Diagnostic & passive probe counter | Full automated endpoint filtering |
| Anonymous `/wp-json/wp/v2/posts` & WooCommerce excerpt truncation | Audit only | Automatic (keeps Gutenberg & Checkout intact) |
| W3C `TDM-Reservation: 1` & `X-Robots-Tag: noai, noimageai` | — | Automatic HTTP & `/.well-known/tdmrep.json` |
| Invisible Unicode provenance watermark & leak decoder | — | Included + scraper IP rate-limiting |

Upgrade & Live WP-Admin Sandbox: [gexiro.com/en/products/content-guardian-pro](https://gexiro.com/en/products/content-guardian-pro) (code **`LAUNCH25`** takes 25% off first year, `14-day` money-back guarantee).

---

Licensed under the [GNU General Public License v2.0 or later](LICENSE). Published by [Gexiro Global Enterprises Ltd](https://gexiro.com).
