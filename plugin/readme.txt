=== Gexiro Content Protection ===
Contributors: gexiro
Tags: ai-crawlers, anti-scraping, woocommerce, rest-api, copy-protection
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 3.3.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Stop casual copy-paste & image theft, keep WooCommerce checkout open, and audit WordPress REST API & AI crawler scraping exposure.

== Description ==

**Content Guardian (Gexiro Content Protection)** by [Gexiro](https://gexiro.com) protects your WordPress articles, guides, and media against casual copying with no external JavaScript dependencies and cross-platform keyboard and clipboard handling across Windows, macOS (`Cmd` key), iOS, and Android.

= Core Features (Included in Free) =

* **Right-Click Context Menu Shield:** Blocks right-click context menu access across protected frontend pages and posts.
* **Smart Text Selection Shield:** Prevents unauthorized text highlighting while automatically preserving interactive `<input>`, `<textarea>`, and `contenteditable` form fields (comments, search bars, and checkout forms remain usable).
* **Cross-Platform Copy, Cut & Select-All Shield:** Intercepts `Ctrl+C`, `Ctrl+X`, `Ctrl+A` (Windows/Linux) and `Cmd+C`, `Cmd+X`, `Cmd+A` (macOS) as well as DOM `copy` and `cut` events.
* **Image Drag & Save Shield:** Prevents visitors from dragging images and SVG illustrations directly out of the browser window.
* **Privacy-First Local Statistics:** Logs blocked protection events locally in WordPress with automatic IP anonymization (`wp_privacy_anonymize_ip`) and automatic 90-day retention pruning.
* **Administrator Bypass & Clean Uninstall:** Allows logged-in administrators to inspect and copy content normally, yields cleanly if Content Guardian Pro is activated, and provides an opt-in clean-uninstall toggle.
* **Local REST API, RSS & AI Crawler Exposure Audit:** Inspects whether your `/wp-json/wp/v2/posts` and `/feed/` endpoints expose full post HTML locally and logs passive `GPTBot`, `ClaudeBot`, `Bytespider`, and unauthenticated REST probes in your dashboard (zero external requests).
* **Broad PHP 7.4–8.4 Compatibility:** Tested and supported across WordPress 6.2 through 7.1 on PHP 7.4, 8.0, 8.1, 8.2, 8.3, and 8.4.

= Engineering Architecture (Included in Free) =

* **Zero External Requests:** All frontend scripts and styles are registered and enqueued locally via standard WordPress APIs (`wp_add_inline_style` and `wp_add_inline_script`).
* **Form-Aware DOM Event Filtering:** Inspects event targets before blocking keyboard shortcuts or selection so standard visitor workflows (login forms, WooCommerce checkout, comments, and search fields) continue to function normally.
* **Prepared SQL & Capability Guards:** Uses WordPress 6.2+ `%i` identifier placeholders for database queries and enforces `manage_options` and nonce verification on all admin actions.

= Privacy & Data Storage =

Gexiro Content Protection Free makes **zero external HTTP requests** and sends no visitor data to third-party servers. Protection event counts (right-click, copy, and drag attempts) are stored exclusively in your local WordPress database (`{prefix}_content_guardian_statistics`) with visitor IP addresses automatically anonymized via WordPress core `wp_privacy_anonymize_ip()` and automatically pruned after 90 days.

= Optional Commercial Capabilities (Content Guardian Pro) =

For digital publishers, media newsrooms, and WordPress agencies requiring automated feed protection and leak attribution beyond browser-layer deterrents, [Content Guardian Pro](https://gexiro.com/en/products/content-guardian-pro) (commercial plans from $39/year for 1–25 sites) adds 17 advanced protection modules:

* **Automated Feed & Content Excerpt Protection:** Replaces full-text syndication and unauthenticated content feeds with clean custom summaries while keeping the Gutenberg block editor, WooCommerce, and search indexing working seamlessly.
* **AI Crawler Opt-Out & Publisher Rights Signals:** Publishes industry-standard AI training opt-out headers and machine-readable policy directives so automated dataset harvesters respect your copyright preferences.
* **Invisible Leak Attribution & Content Watermarking:** Embeds an invisible, reader-safe provenance marker into protected articles with a built-in WordPress admin decoder to verify ownership if your content is republished without permission.
* **Protected Content Vault (`[guardian_vault]`) & Visual Image Shield:** Wraps premium research paragraphs inside an isolated reader container and protects original high-resolution photographs and illustrations against bulk extraction.
* **Automated Scraper Rate-Limiting & Self-Service License Management:** Automatically throttles high-velocity automated harvesting bots and includes self-service domain seat transfers and one-click WordPress updates.

= Documentation & Hardening Guides =

* [WordPress & WooCommerce Anti-Scraping Hardening Guides](https://gexiro.com/en/guides) — Technical guides for securing `/wp-json/wp/v2/posts`, WooCommerce Store API endpoints, full-text RSS `<content:encoded>` feeds, and W3C `TDM-Reservation` AI crawler policies.
* [Free WordPress & WooCommerce Exposure Check](https://gexiro.com/en/tools/wordpress-exposure-check) — Read-only external check for `/wp-json/wp/v2/posts`, Store API, RSS, and `robots.txt` scraping exposure.
* [Free WooCommerce Catalog Scraping Check](https://gexiro.com/en/tools/woocommerce-scraping-check) — Inspect public `/wp-json/wc/store/v1/products` catalog exposure without touching checkout.
* [Free AI Crawler & robots.txt Opt-Out Checker](https://gexiro.com/en/tools/ai-crawler-robots-check) — Audit `GPTBot`, `ClaudeBot`, `CCBot`, `Google-Extended`, `Bytespider`, and W3C `TDM-Reservation` headers.
* [Free WordPress RSS Feed Scraping & Leak Check](https://gexiro.com/en/tools/wordpress-rss-leak-check) — Check `/feed/` for `<content:encoded>` full-text leaks and REST API fallbacks.

== Installation ==

1. Upload the `gexiro-content-protection` folder to `/wp-content/plugins/` or install the ZIP archive via **Plugins -> Add New -> Upload Plugin**.
2. Activate **Gexiro Content Protection** through the **Plugins** menu in WordPress.
3. Configure your protection preferences under **Gexiro Content Protection -> Protection Settings**.

== Frequently Asked Questions ==

= Will Gexiro Content Protection break comment forms, WooCommerce checkout, or search inputs? =

No. Gexiro Content Protection explicitly excludes `<input>`, `<textarea>`, and `contenteditable="true"` elements from text-selection and keyboard blocking so visitors can type, paste, and submit forms normally.

= How does Gexiro Content Protection minimize frontend performance impact? =

The Free edition uses lightweight inline CSS rules and a small vanilla JavaScript listener (< 4 KB unminified) with zero external script files, zero jQuery dependency on the frontend, and no layout-shifting DOM overlays. Actual page performance metrics depend on your active theme, hosting environment, and other installed plugins.

= How does Gexiro Content Protection handle visitor privacy and data minimization? =

The Free edition operates locally on your WordPress installation, sends zero telemetry to external servers, anonymizes visitor IP addresses via `wp_privacy_anonymize_ip()` before storing event counters, automatically prunes statistics older than 90 days, and removes its database table upon uninstall when Clean Uninstall is enabled. Note that overall regulatory compliance (such as GDPR) depends on your site's specific configuration, privacy disclosures, and legal obligations.

= How do I upgrade from Free to Content Guardian Pro? =

Simply upload and activate **Content Guardian Pro** via **Plugins -> Add New -> Upload Plugin**. Gexiro Content Protection Free automatically yields without class conflicts and preserves your existing settings and statistics.

== Screenshots ==

1. Gexiro Content Protection Dashboard — 4-layer core protection status and live telemetry overview.
2. Protection Settings — Granular toggles for right-click, text selection, clipboard, drag-and-drop, custom alert message, and clean uninstall.
3. Protection Statistics — Anonymized local event analytics and 90-day retention controls.
4. Optional Pro Modules Overview — 17 commercial publisher protection modules including automated feed summaries, AI crawler opt-out signals, and invisible leak attribution.
5. Form-Safe Frontend Protection — Protected WordPress article shielding body copy while keeping reader comment and search inputs interactive.

== Changelog ==

= 3.3.7 =
* Added direct external WordPress & WooCommerce exposure check link inside the Dashboard Site Exposure Self-Audit card.
* Added a dismissible, non-intrusive WordPress.org review request notice limited strictly to Content Guardian admin screens after 7 days active or 10 blocked events (with clean uninstall cleanup).
* Updated plugin directory metadata, hardening guide links, and WooCommerce / AI-crawler audit clarity.

= 3.3.6 =
* Lowered minimum PHP requirement from 8.2 to 7.4 (compatible with PHP 7.4 through 8.4 while keeping WordPress 6.2+ `%i` prepared SQL security).
* Added Site Exposure Self-Audit panel in Dashboard checking local `/wp-json/wp/v2/posts`, WooCommerce Store API, and `/feed/` full-text exposure.
* Added lightweight throttled passive observer for anonymous REST scrape pulls and AI crawler hits (`GPTBot`, `ClaudeBot`, `Bytespider`, `CCBot`, CLI clients) stored locally with anonymized IPs.
* Added dismissible post-activation exposure notice on the Plugins and Dashboard screens.

= 3.3.5 =
* Added direct WordPress.org support and review links in the plugin dashboard.
* Added Early-Adopter LAUNCH25 upgrade offer details and verified seamless coexistence with Content Guardian Pro 3.3.5.

= 3.3.4 =
* Renamed plugin display name to Gexiro Content Protection (`gexiro-content-protection`) per WordPress.org Plugin Review Team guidance.
* Refined `uninstall.php` coexistence detection and moved admin menu placement to default bottom position.
* Verified compatibility with WordPress 7.1.
* Updated frontend telemetry requests to use `application/x-www-form-urlencoded` (`URLSearchParams`) with server-side `multipart/form-data` rejection.
* Enforced PHP 8.2+ minimum runtime requirement in plugin headers.
* Verified clean coexistence and preference handover when upgrading alongside Content Guardian Pro 3.3.4.

= 3.3.3 =
* Added static Gexiro Ecosystem overview card on the dashboard for external DNS, TLS, and HTTP security posture visibility.
* Improved Free-to-Pro feature comparison table clarity across all 4 Free modules and 17 Pro modules.

= 3.3.2 =
* Registered official WordPress Core Privacy Policy disclosure snippet (`wp_add_privacy_policy_content`).
* Standardized honest technical terminology across documentation and admin UI.
* Bundled full physical GNU General Public License v2.0 (`LICENSE`) file.

= 3.3.0 =
* Added dedicated `Content_Guardian_Free_*` class isolation and automatic coexistence yield when upgrading alongside Content Guardian Pro.
* Migrated frontend protection styles and scripts to `wp_add_inline_style()` and `wp_add_inline_script()`.
* Added IP anonymization (`wp_privacy_anonymize_ip`) and 90-day automatic pruning for local event statistics.
* Added WordPress 6.2+ `%i` prepared SQL table identifier support and opt-in Clean Uninstall handler (`uninstall.php`).

== Upgrade Notice ==

= 3.3.7 =
Adds external WordPress/WooCommerce/AI exposure checker links inside Site Exposure Self-Audit, clean admin-scoped review prompt with opt-in uninstall cleanup, and updated hardening guides.

= 3.3.6 =
Unlocks PHP 7.4–8.4 support and adds a zero-external-request Site Exposure Self-Audit for REST API, RSS feeds, and AI crawler probes.

= 3.3.5 =
Dashboard enhancements, WordPress.org support/review links, and Content Guardian Pro 3.3.5 compatibility.

= 3.3.4 =
Compatibility update for WordPress 7.1 and PHP 8.2+ with `URLSearchParams` telemetry hardening and WordPress.org review compliance updates.
