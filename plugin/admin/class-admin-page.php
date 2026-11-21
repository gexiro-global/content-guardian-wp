<?php
/**
 * Content Guardian - Free Version Admin Page
 *
 * Handles dashboard, settings, and statistics UI for Content Guardian FREE.
 *
 * @package ContentGuardian
 * @version 3.3.7
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Content_Guardian_Free_Admin_Page', false)) {

    class Content_Guardian_Free_Admin_Page {

        private string $upgrade_url;

        public function __construct() {
            $this->upgrade_url = defined('CONTENT_GUARDIAN_PRO_UPGRADE_URL')
                ? CONTENT_GUARDIAN_PRO_UPGRADE_URL
                : 'https://gexiro.com/en/products/content-guardian-pro';
        }

        public function render_dashboard(): void {
            $stats = new Content_Guardian_Free_Statistics_Manager();
            $data = $stats->get_protection_stats();

            $post_obj = wp_count_posts('post');
            $published_posts = isset($post_obj->publish) ? (int) $post_obj->publish : 0;
            $woo_products = 0;
            if (function_exists('post_type_exists') && post_type_exists('product')) {
                $woo_obj = wp_count_posts('product');
                $woo_products = isset($woo_obj->publish) ? (int) $woo_obj->publish : 0;
            }
            $rest_enabled = (bool) apply_filters('rest_enabled', true);
            $rss_full_text = ((int) get_option('rss_use_excerpt', 0)) === 0;
            $automated_probes = (int) ($data['automated_probes_total'] ?? 0);
            $rest_sample_url = function_exists('rest_url') ? rest_url('wp/v2/posts?per_page=3') : home_url('/wp-json/wp/v2/posts?per_page=3');
            $audit_upgrade_url = $this->upgrade_url . '?utm_source=wp-plugin&utm_medium=exposure-audit&utm_campaign=v337#pricing';
            $banner_upgrade_url = $this->upgrade_url . '?utm_source=wp-plugin&utm_medium=dashboard-banner&utm_campaign=v337#pricing';
            $eco_upgrade_url = $this->upgrade_url . '?utm_source=wp-plugin&utm_medium=ecosystem-box&utm_campaign=v337#ecosystem';
            ?>
            <div class="wrap">
                <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #f8fafc; padding: 22px 24px; margin: 0 -20px 20px -20px; border-radius: 8px; border-bottom: 3px solid #10b981;">
                    <h1 style="color: #f8fafc; margin: 0; font-size: 22px; font-weight: 700;">
                        <?php esc_html_e('Gexiro Content Protection - FREE Edition', 'gexiro-content-protection'); ?>
                        <span style="background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); padding: 3px 10px; border-radius: 12px; font-size: 12px; margin-left: 10px;">v<?php echo esc_html(CONTENT_GUARDIAN_VERSION); ?></span>
                    </h1>
                    <p style="margin: 8px 0 0 0; color: #94a3b8; font-size: 14px;">
                        <?php esc_html_e('4 Core Protection Modules Active • Local Exposure Self-Audit • Anonymized Local Telemetry', 'gexiro-content-protection'); ?>
                    </p>
                </div>

                <div id="cg-exposure-audit" class="postbox" style="border-left: 4px solid #dc2626; margin: 20px 0;">
                    <h3 class="hndle" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                        <span><?php esc_html_e('Site Exposure Self-Audit (Headless Scrapers & AI Crawlers)', 'gexiro-content-protection'); ?></span>
                        <span style="background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 2px 10px; border-radius: 4px; font-size: 11px; font-weight: 700;">
                            <?php esc_html_e('LOCAL DIAGNOSTIC · ZERO EXTERNAL REQUESTS', 'gexiro-content-protection'); ?>
                        </span>
                    </h3>
                    <div class="inside">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 15px;">
                            <div style="background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid <?php echo $rest_enabled ? '#dc2626' : '#059669'; ?>; border-radius: 6px; padding: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                                    <strong style="color: #0f172a;"><?php esc_html_e('Public REST API (/wp-json/wp/v2/posts)', 'gexiro-content-protection'); ?></strong>
                                    <span style="color: <?php echo $rest_enabled ? '#dc2626' : '#059669'; ?>; font-size: 11px; font-weight: 700;">
                                        <?php echo $rest_enabled ? esc_html__('EXPOSED IN FREE', 'gexiro-content-protection') : esc_html__('FILTERED', 'gexiro-content-protection'); ?>
                                    </span>
                                </div>
                                <p style="margin: 8px 0; color: #475569; font-size: 12px;">
                                    <?php
                                    if ($woo_products > 0) {
                                        printf(
                                            /* translators: 1: post count, 2: WooCommerce product count */
                                            esc_html__('%1$d published posts and %2$d WooCommerce products are readable as raw full-text JSON by scrapers bypassing browser right-click locks.', 'gexiro-content-protection'),
                                            (int) $published_posts,
                                            (int) $woo_products
                                        );
                                    } else {
                                        printf(
                                            /* translators: %d: post count */
                                            esc_html__('%d published posts are readable in full HTML via /wp-json/wp/v2/posts (100 per request) without triggering browser copy shields.', 'gexiro-content-protection'),
                                            (int) $published_posts
                                        );
                                    }
                                    ?>
                                </p>
                                <a href="<?php echo esc_url($rest_sample_url); ?>" target="_blank" rel="noopener" style="font-size: 12px; font-weight: 600; color: #0284c7; text-decoration: none;">
                                    <?php esc_html_e('Inspect Your Live /wp-json/ Feed', 'gexiro-content-protection'); ?> &rarr;
                                </a>
                                <br>
                                <a href="<?php echo esc_url('https://gexiro.com/en/tools/wordpress-exposure-check?utm_source=wp-plugin&utm_medium=exposure-audit&utm_campaign=v337'); ?>" target="_blank" rel="noopener" style="display: inline-block; margin-top: 6px; font-size: 12px; font-weight: 600; color: #059669; text-decoration: none;">
                                    <?php esc_html_e('Run Free External Exposure Check (REST, WooCommerce, RSS & AI)', 'gexiro-content-protection'); ?> &rarr;
                                </a>
                            </div>

                            <div style="background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid <?php echo $rss_full_text ? '#d97706' : '#059669'; ?>; border-radius: 6px; padding: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                                    <strong style="color: #0f172a;"><?php esc_html_e('RSS Syndication Feed (/feed/)', 'gexiro-content-protection'); ?></strong>
                                    <span style="color: <?php echo $rss_full_text ? '#d97706' : '#059669'; ?>; font-size: 11px; font-weight: 700;">
                                        <?php echo $rss_full_text ? esc_html__('FULL-TEXT EXPOSED', 'gexiro-content-protection') : esc_html__('EXCERPT ONLY', 'gexiro-content-protection'); ?>
                                    </span>
                                </div>
                                <p style="margin: 8px 0 0 0; color: #475569; font-size: 12px;">
                                    <?php
                                    echo $rss_full_text
                                        ? esc_html__('WordPress is serving complete article HTML in RSS feeds, allowing automated autoblogs to republish posts immediately.', 'gexiro-content-protection')
                                        : esc_html__('WordPress Settings > Reading is set to Excerpt, while Pro adds canonical source links and AI TDM headers.', 'gexiro-content-protection');
                                    ?>
                                </p>
                            </div>

                            <div style="background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid #0284c7; border-radius: 6px; padding: 15px; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                                        <strong style="color: #0f172a;"><?php esc_html_e('Automated AI & REST Probes', 'gexiro-content-protection'); ?></strong>
                                        <span style="color: #0284c7; font-size: 14px; font-weight: 700;"><?php echo esc_html(number_format_i18n($automated_probes)); ?></span>
                                    </div>
                                    <p style="margin: 8px 0 12px 0; color: #475569; font-size: 12px;">
                                        <?php
                                        printf(
                                            /* translators: 1: REST probes count, 2: AI bot probes count */
                                            esc_html__('Detected %1$d anonymous REST pulls and %2$d AI crawler / CLI scraper hits (GPTBot, ClaudeBot, Bytespider, curl).', 'gexiro-content-protection'),
                                            (int) ($data['rest_scrape_probes'] ?? 0),
                                            (int) ($data['ai_bot_probes'] ?? 0)
                                        );
                                        ?>
                                    </p>
                                </div>
                                <a href="<?php echo esc_url($audit_upgrade_url); ?>" target="_blank" rel="noopener" class="button button-primary" style="background: #059669; border-color: #047857; color: #ffffff; font-weight: 600; text-align: center;">
                                    <?php esc_html_e('Lock REST API, RSS & AI Crawlers in PRO — $29.25/yr (LAUNCH25)', 'gexiro-content-protection'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="background: #0f172a; color: #f8fafc; padding: 20px 24px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #10b981; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
                    <div>
                        <h2 style="color: #f8fafc; margin: 0 0 6px 0; font-size: 18px;"><?php esc_html_e('Unlock All 21 Enterprise Protection Layers', 'gexiro-content-protection'); ?></h2>
                        <p style="margin: 0; color: #94a3b8; font-size: 13px;">
                            <?php esc_html_e('Upgrade to Content Guardian Pro for Headless REST/RSS Shield, W3C TDM-Reservation headers, Opt-In Forensic Canary DRM, Closed Shadow-DOM Vaults, Black October Shield, and Cloudflare Edge-WAF.', 'gexiro-content-protection'); ?>
                        </p>
                        <p style="margin: 6px 0 0 0; color: #34d399; font-size: 12px; font-weight: 600;">
                            <?php esc_html_e('Early-Adopter Offer: Use code LAUNCH25 at checkout for 25% OFF your first year.', 'gexiro-content-protection'); ?>
                        </p>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <a href="<?php echo esc_url($banner_upgrade_url); ?>" target="_blank" rel="noopener" class="button button-primary" style="background: #10b981; border-color: #059669; color: #ffffff; font-weight: 600; padding: 8px 20px;">
                            <?php esc_html_e('Upgrade to PRO (-25% Code: LAUNCH25)', 'gexiro-content-protection'); ?>
                        </a>
                        <a href="<?php echo esc_url('https://wordpress.org/support/plugin/gexiro-content-protection/reviews/#new-post'); ?>" target="_blank" rel="noopener" class="button button-secondary" style="background: #1e293b; border-color: #334155; color: #f8fafc; padding: 8px 14px;">
                            <?php esc_html_e('Rate 5 Stars on WordPress.org', 'gexiro-content-protection'); ?>
                        </a>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
                    <div style="background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; border-left: 4px solid #10b981;">
                        <div style="font-size: 18px; font-weight: 700; color: #059669;"><?php esc_html_e('FREE EDITION', 'gexiro-content-protection'); ?></div>
                        <div style="color: #64748b; font-size: 12px; margin-top: 4px;"><?php esc_html_e('4 Core Modules Active', 'gexiro-content-protection'); ?></div>
                    </div>
                    <div style="background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; border-left: 4px solid #0284c7;">
                        <div style="font-size: 18px; font-weight: 700; color: #0284c7;"><?php esc_html_e('CROSS-PLATFORM', 'gexiro-content-protection'); ?></div>
                        <div style="color: #64748b; font-size: 12px; margin-top: 4px;"><?php esc_html_e('Windows, macOS, iOS & Android', 'gexiro-content-protection'); ?></div>
                    </div>
                    <div style="background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; border-left: 4px solid #0f172a;">
                        <div style="font-size: 18px; font-weight: 700; color: #0f172a;"><?php esc_html_e('LOW OVERHEAD', 'gexiro-content-protection'); ?></div>
                        <div style="color: #64748b; font-size: 12px; margin-top: 4px;"><?php esc_html_e('Zero External JS Dependencies', 'gexiro-content-protection'); ?></div>
                    </div>
                    <div style="background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; border-left: 4px solid #10b981;">
                        <div style="font-size: 18px; font-weight: 700; color: #059669;"><?php esc_html_e('PRIVACY FIRST', 'gexiro-content-protection'); ?></div>
                        <div style="color: #64748b; font-size: 12px; margin-top: 4px;"><?php esc_html_e('Anonymized Local Telemetry', 'gexiro-content-protection'); ?></div>
                    </div>
                </div>

                <div class="postbox">
                    <h3 class="hndle"><?php esc_html_e('Protection Features Status', 'gexiro-content-protection'); ?></h3>
                    <div class="inside">
                        <?php
                        $free_features = ['disable_right_click', 'disable_text_selection', 'disable_copy_paste', 'disable_drag_drop'];

                        $protections = [
                            'disable_right_click' => ['name' => esc_html__('Right-Click Protection', 'gexiro-content-protection'), 'desc' => esc_html__('Context menu disabled across frontend pages', 'gexiro-content-protection'), 'tier' => 'FREE'],
                            'disable_text_selection' => ['name' => esc_html__('Text Selection Protection', 'gexiro-content-protection'), 'desc' => esc_html__('Text highlighting disabled while preserving form inputs', 'gexiro-content-protection'), 'tier' => 'FREE'],
                            'disable_copy_paste' => ['name' => esc_html__('Copy/Cut & Clipboard Protection', 'gexiro-content-protection'), 'desc' => esc_html__('Ctrl/Cmd+C, Ctrl/Cmd+X, Ctrl/Cmd+A and clipboard events blocked', 'gexiro-content-protection'), 'tier' => 'FREE'],
                            'disable_drag_drop' => ['name' => esc_html__('Image Drag & Save Shield', 'gexiro-content-protection'), 'desc' => esc_html__('Prevents dragging images and SVG graphics out of the browser window', 'gexiro-content-protection'), 'tier' => 'FREE'],
                            'disable_f12' => ['name' => esc_html__('DevTools Protection (F12)', 'gexiro-content-protection'), 'desc' => esc_html__('Developer tools shortcut access blocked', 'gexiro-content-protection'), 'tier' => 'PRO'],
                            'disable_view_source' => ['name' => esc_html__('View Source Protection', 'gexiro-content-protection'), 'desc' => esc_html__('Ctrl/Cmd+U page source viewing blocked', 'gexiro-content-protection'), 'tier' => 'PRO'],
                            'disable_ctrl_s' => ['name' => esc_html__('Save Protection', 'gexiro-content-protection'), 'desc' => esc_html__('Ctrl/Cmd+S page saving blocked', 'gexiro-content-protection'), 'tier' => 'PRO'],
                            'disable_print' => ['name' => esc_html__('Print Protection', 'gexiro-content-protection'), 'desc' => esc_html__('Ctrl/Cmd+P printing blocked', 'gexiro-content-protection'), 'tier' => 'PRO'],
                            'disable_print_screen' => ['name' => esc_html__('Print Screen Protection', 'gexiro-content-protection'), 'desc' => esc_html__('PrintScreen key interception enabled', 'gexiro-content-protection'), 'tier' => 'PRO'],
                            'image_protection' => ['name' => esc_html__('Enhanced Image Overlay Shield', 'gexiro-content-protection'), 'desc' => esc_html__('Transparent DOM shield over gallery and media containers', 'gexiro-content-protection'), 'tier' => 'PRO'],
                            'watermark_enabled' => ['name' => esc_html__('Watermark Overlay', 'gexiro-content-protection'), 'desc' => esc_html__('Semi-transparent content watermarking', 'gexiro-content-protection'), 'tier' => 'PRO'],
                            'mobile_protection' => ['name' => esc_html__('Mobile Protection', 'gexiro-content-protection'), 'desc' => esc_html__('Touch device multi-touch gesture blocking', 'gexiro-content-protection'), 'tier' => 'PRO'],
                            'blur_effect' => ['name' => esc_html__('DevTools Blur Effect', 'gexiro-content-protection'), 'desc' => esc_html__('Content blurred when DevTools detected', 'gexiro-content-protection'), 'tier' => 'PRO'],
                            'forensic_canary' => ['name' => esc_html__('Forensic Zero-Width Canary DRM', 'gexiro-content-protection'), 'desc' => esc_html__('Invisible cryptographic watermark & leak decoder', 'gexiro-content-protection'), 'tier' => 'PRO'],
                            'ai_scraper_trap' => ['name' => esc_html__('AI Scraper & LLM Poisoning Trap', 'gexiro-content-protection'), 'desc' => esc_html__('Anti-RAG prompt directives and invisible bot honeypot', 'gexiro-content-protection'), 'tier' => 'PRO'],
                            'glyph_obfuscation' => ['name' => esc_html__('Closed Shadow-DOM Vault [guardian_vault]', 'gexiro-content-protection'), 'desc' => esc_html__('Server-obfuscated HTML payload rendered in closed Shadow DOM', 'gexiro-content-protection'), 'tier' => 'PRO'],
                            'canvas_image_shield' => ['name' => esc_html__('Canvas Pixel-Watermarked Images', 'gexiro-content-protection'), 'desc' => esc_html__('Replaces <img> tags with pixel-watermarked HTML5 Canvas', 'gexiro-content-protection'), 'tier' => 'PRO'],
                            'edge_waf_autoban' => ['name' => esc_html__('Edge-WAF & Cloudflare Auto-Ban', 'gexiro-content-protection'), 'desc' => esc_html__('Automatic IP firewall & Cloudflare Edge challenge', 'gexiro-content-protection'), 'tier' => 'PRO'],
                            'black_october_shield' => ['name' => esc_html__('Black October & PHP2Shell Shield', 'gexiro-content-protection'), 'desc' => esc_html__('Runtime CVE-2026-109619 guard & Ed25519 signed updates', 'gexiro-content-protection'), 'tier' => 'PRO'],
                            'rest_rss_shield' => ['name' => esc_html__('Headless REST, RSS, oEmbed & XML-RPC Shield', 'gexiro-content-protection'), 'desc' => esc_html__('Blocks full-text JSON/RSS scraping (/wp-json/wp/v2/posts & /feed/)', 'gexiro-content-protection'), 'tier' => 'PRO'],
                            'ai_tdm_reservation' => ['name' => esc_html__('W3C TDM-Reservation & /llms.txt Policy', 'gexiro-content-protection'), 'desc' => esc_html__('Serves W3C TDM-Reservation: 1, noai headers, and /llms.txt', 'gexiro-content-protection'), 'tier' => 'PRO']
                        ];
                        ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px;">
                            <?php foreach ($protections as $key => $protection): ?>
                                <?php
                                $is_available = in_array($key, $free_features, true);
                                $is_active = $is_available && (bool) get_option('content_guardian_' . $key, 1);

                                if ($is_available) {
                                    $bg_color = $is_active ? '#f0fdf4' : '#f8fafc';
                                    $border_color = $is_active ? '#10b981' : '#cbd5e1';
                                    $status = $is_active ? esc_html__('ACTIVE', 'gexiro-content-protection') : esc_html__('DISABLED', 'gexiro-content-protection');
                                    $status_color = $is_active ? '#059669' : '#64748b';
                                } else {
                                    $bg_color = '#f8fafc';
                                    $border_color = '#cbd5e1';
                                    $status = esc_html__('PRO ONLY', 'gexiro-content-protection');
                                    $status_color = '#dc2626';
                                }
                                ?>
                                <div style="background: <?php echo esc_attr($bg_color); ?>; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; border-left: 4px solid <?php echo esc_attr($border_color); ?>; <?php echo !$is_available ? 'opacity: 0.75;' : ''; ?>">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <strong style="color: #0f172a;"><?php echo esc_html($protection['name']); ?></strong>
                                        <span style="color: <?php echo esc_attr($status_color); ?>; font-size: 11px; font-weight: 700;"><?php echo esc_html($status); ?></span>
                                    </div>
                                    <p style="margin: 6px 0 0 0; color: #475569; font-size: 12px;"><?php echo esc_html($protection['desc']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="postbox">
                    <h3 class="hndle"><?php esc_html_e('Protection Statistics (Live Telemetry)', 'gexiro-content-protection'); ?></h3>
                    <div class="inside">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                            <div style="text-align: center; padding: 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                                <div style="font-size: 24px; font-weight: 700; color: #0f172a;"><?php echo esc_html(number_format_i18n($data['total_blocks'])); ?></div>
                                <div style="font-size: 12px; color: #64748b;"><?php esc_html_e('Total Blocks', 'gexiro-content-protection'); ?></div>
                            </div>
                            <div style="text-align: center; padding: 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                                <div style="font-size: 24px; font-weight: 700; color: #0284c7;"><?php echo esc_html(number_format_i18n($data['right_clicks_blocked'])); ?></div>
                                <div style="font-size: 12px; color: #64748b;"><?php esc_html_e('Right-Clicks Blocked', 'gexiro-content-protection'); ?></div>
                            </div>
                            <div style="text-align: center; padding: 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                                <div style="font-size: 24px; font-weight: 700; color: #059669;"><?php echo esc_html(number_format_i18n($data['copy_attempts_blocked'])); ?></div>
                                <div style="font-size: 12px; color: #64748b;"><?php esc_html_e('Copy Attempts Blocked', 'gexiro-content-protection'); ?></div>
                            </div>
                            <div style="text-align: center; padding: 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                                <div style="font-size: 24px; font-weight: 700; color: #d97706;"><?php echo esc_html(number_format_i18n($data['today_blocks'])); ?></div>
                                <div style="font-size: 12px; color: #64748b;"><?php esc_html_e('Today\'s Blocks', 'gexiro-content-protection'); ?></div>
                            </div>
                            <div style="text-align: center; padding: 15px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px;">
                                <div style="font-size: 24px; font-weight: 700; color: #dc2626;"><?php echo esc_html(number_format_i18n($data['automated_probes_total'] ?? 0)); ?></div>
                                <div style="font-size: 12px; color: #991b1b;"><?php esc_html_e('AI & REST Probes', 'gexiro-content-protection'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="postbox">
                    <h3 class="hndle"><?php esc_html_e('Extend Your Security Visibility — Gexiro Ecosystem', 'gexiro-content-protection'); ?></h3>
                    <div class="inside">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
                            <p style="margin: 0; color: #475569; font-size: 13px; max-width: 780px;">
                                <?php esc_html_e('Gexiro Content Protection protects published content and media inside WordPress. To inspect external DNS, TLS configuration, HTTP security headers, and public web attack-surface posture across your domains, explore ChimeraScope — Gexiro\'s self-service web security posture platform.', 'gexiro-content-protection'); ?>
                            </p>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <a href="<?php echo esc_url('https://chimerascope.com'); ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary">
                                    <?php esc_html_e('Explore ChimeraScope', 'gexiro-content-protection'); ?> &rarr;
                                </a>
                                <a href="<?php echo esc_url($eco_upgrade_url); ?>" target="_blank" rel="noopener" class="button button-link" style="text-decoration: none;">
                                    <?php esc_html_e('Ecosystem Overview', 'gexiro-content-protection'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        public function render_settings_page(): void {
            $free_features = ['disable_right_click', 'disable_text_selection', 'disable_copy_paste', 'disable_drag_drop'];
            $settings_saved = false;

            if (isset($_POST['submit'], $_POST['_wpnonce']) &&
                current_user_can('manage_options') &&
                wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'content_guardian_settings')) {

                update_option('content_guardian_admin_bypass', isset($_POST['admin_bypass']) ? 1 : 0);
                update_option('content_guardian_show_alerts', isset($_POST['show_alerts']) ? 1 : 0);
                update_option('content_guardian_delete_data_on_uninstall', isset($_POST['delete_data_on_uninstall']) ? 1 : 0);

                if (isset($_POST['custom_message'])) {
                    update_option('content_guardian_custom_message', sanitize_text_field(wp_unslash($_POST['custom_message'])));
                }

                foreach ($free_features as $feature) {
                    update_option('content_guardian_' . $feature, isset($_POST[$feature]) ? 1 : 0);
                }

                $settings_saved = true;
            }

            $admin_bypass = (int) get_option('content_guardian_admin_bypass', 1);
            $show_alerts = (int) get_option('content_guardian_show_alerts', 0);
            $delete_data_on_uninstall = (int) get_option('content_guardian_delete_data_on_uninstall', 0);
            $custom_message = (string) get_option('content_guardian_custom_message', 'Content is protected!');
            $disable_right_click = (int) get_option('content_guardian_disable_right_click', 1);
            $disable_text_selection = (int) get_option('content_guardian_disable_text_selection', 1);
            $disable_copy_paste = (int) get_option('content_guardian_disable_copy_paste', 1);
            $disable_drag_drop = (int) get_option('content_guardian_disable_drag_drop', 1);
            ?>
            <div class="wrap">
                <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #f8fafc; padding: 22px 24px; margin: 0 -20px 20px -20px; border-radius: 8px; border-bottom: 3px solid #10b981;">
                    <h1 style="color: #f8fafc; margin: 0; font-size: 22px; font-weight: 700;"><?php esc_html_e('Protection Settings', 'gexiro-content-protection'); ?></h1>
                    <p style="margin: 8px 0 0 0; color: #94a3b8; font-size: 14px;"><?php esc_html_e('Configure your core content protection preferences.', 'gexiro-content-protection'); ?></p>
                </div>

                <?php if ($settings_saved): ?>
                <div id="settings-saved-notice" style="background: #ecfdf5; border: 1px solid #10b981; padding: 14px; margin: 20px 0; border-radius: 6px; border-left: 4px solid #059669;">
                    <p style="margin: 0; color: #065f46;"><strong><?php esc_html_e('Settings saved successfully.', 'gexiro-content-protection'); ?></strong></p>
                </div>
                <?php endif; ?>

                <form method="post" action="">
                    <?php wp_nonce_field('content_guardian_settings'); ?>

                    <div class="postbox">
                        <h3 class="hndle"><?php esc_html_e('Core Protection Features (FREE)', 'gexiro-content-protection'); ?></h3>
                        <div class="inside">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-bottom: 30px;">
                                <div style="background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; border-left: 4px solid #0284c7;">
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="disable_right_click" <?php checked($disable_right_click, 1); ?> style="margin-right: 10px;" />
                                        <strong style="color: #0f172a;"><?php esc_html_e('Right-Click Protection', 'gexiro-content-protection'); ?></strong>
                                    </label>
                                    <p style="margin: 6px 0 0 24px; font-size: 12px; color: #475569;"><?php esc_html_e('Disable context menu on frontend pages', 'gexiro-content-protection'); ?></p>
                                </div>

                                <div style="background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; border-left: 4px solid #0284c7;">
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="disable_text_selection" <?php checked($disable_text_selection, 1); ?> style="margin-right: 10px;" />
                                        <strong style="color: #0f172a;"><?php esc_html_e('Text Selection Protection', 'gexiro-content-protection'); ?></strong>
                                    </label>
                                    <p style="margin: 6px 0 0 24px; font-size: 12px; color: #475569;"><?php esc_html_e('Prevent text highlighting while preserving form inputs', 'gexiro-content-protection'); ?></p>
                                </div>

                                <div style="background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; border-left: 4px solid #0284c7;">
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="disable_copy_paste" <?php checked($disable_copy_paste, 1); ?> style="margin-right: 10px;" />
                                        <strong style="color: #0f172a;"><?php esc_html_e('Copy/Cut & Clipboard Protection', 'gexiro-content-protection'); ?></strong>
                                    </label>
                                    <p style="margin: 6px 0 0 24px; font-size: 12px; color: #475569;"><?php esc_html_e('Block Ctrl/Cmd+C, V, A, X and copy/cut DOM events', 'gexiro-content-protection'); ?></p>
                                </div>

                                <div style="background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; border-left: 4px solid #0284c7;">
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="disable_drag_drop" <?php checked($disable_drag_drop, 1); ?> style="margin-right: 10px;" />
                                        <strong style="color: #0f172a;"><?php esc_html_e('Image Drag & Save Shield', 'gexiro-content-protection'); ?></strong>
                                    </label>
                                    <p style="margin: 6px 0 0 24px; font-size: 12px; color: #475569;"><?php esc_html_e('Prevent dragging images and SVG media out of pages', 'gexiro-content-protection'); ?></p>
                                </div>
                            </div>

                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="admin_bypass"><?php esc_html_e('Admin Bypass', 'gexiro-content-protection'); ?></label></th>
                                    <td>
                                        <input type="checkbox" id="admin_bypass" name="admin_bypass" <?php checked($admin_bypass, 1); ?> />
                                        <label for="admin_bypass"><?php esc_html_e('Allow logged-in WordPress administrators to bypass all protection', 'gexiro-content-protection'); ?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="show_alerts"><?php esc_html_e('Show Alert Messages', 'gexiro-content-protection'); ?></label></th>
                                    <td>
                                        <input type="checkbox" id="show_alerts" name="show_alerts" <?php checked($show_alerts, 1); ?> />
                                        <label for="show_alerts"><?php esc_html_e('Display alert message when protection is triggered', 'gexiro-content-protection'); ?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="custom_message"><?php esc_html_e('Custom Alert Message', 'gexiro-content-protection'); ?></label></th>
                                    <td>
                                        <input type="text" id="custom_message" name="custom_message" value="<?php echo esc_attr($custom_message); ?>" class="regular-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="delete_data_on_uninstall"><?php esc_html_e('Clean Uninstall (GDPR / Hygiene)', 'gexiro-content-protection'); ?></label></th>
                                    <td>
                                        <input type="checkbox" id="delete_data_on_uninstall" name="delete_data_on_uninstall" <?php checked($delete_data_on_uninstall, 1); ?> />
                                        <label for="delete_data_on_uninstall"><?php esc_html_e('Permanently delete plugin options and telemetry tables if the plugin is uninstalled', 'gexiro-content-protection'); ?></label>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button-primary" value="<?php esc_attr_e('Save Settings', 'gexiro-content-protection'); ?>" />
                    </p>
                </form>
            </div>
            <?php
        }

        public function render_statistics_page(): void {
            $stats_reset_success = false;

            if (isset($_POST['reset_stats'], $_POST['_wpnonce']) &&
                current_user_can('manage_options') &&
                wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'content_guardian_stats_reset')) {
                $stats_manager = new Content_Guardian_Free_Statistics_Manager();
                if ($stats_manager->reset_statistics()) {
                    $stats_reset_success = true;
                }
            }

            $stats_manager = new Content_Guardian_Free_Statistics_Manager();
            $stats = $stats_manager->get_protection_stats();
            $recent_events = $stats_manager->get_recent_events();
            ?>
            <div class="wrap">
                <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #f8fafc; padding: 22px 24px; margin: 0 -20px 20px -20px; border-radius: 8px; border-bottom: 3px solid #10b981;">
                    <h1 style="color: #f8fafc; margin: 0; font-size: 22px; font-weight: 700;"><?php esc_html_e('Protection Statistics', 'gexiro-content-protection'); ?></h1>
                    <p style="margin: 8px 0 0 0; color: #94a3b8; font-size: 14px;"><?php esc_html_e('Privacy-first local analytics (visitor IP addresses are automatically anonymized).', 'gexiro-content-protection'); ?></p>
                </div>

                <?php if ($stats_reset_success): ?>
                <div id="stats-reset-success-notice" style="background: #ecfdf5; border: 1px solid #10b981; padding: 14px; margin: 20px 0; border-radius: 6px; border-left: 4px solid #059669;">
                    <p style="margin: 0; color: #065f46;"><strong><?php esc_html_e('Statistics reset successfully.', 'gexiro-content-protection'); ?></strong></p>
                </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
                    <div style="background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; border-left: 4px solid #0f172a;">
                        <div style="font-size: 28px; font-weight: 700; color: #0f172a;"><?php echo esc_html(number_format_i18n($stats['total_blocks'])); ?></div>
                        <div style="font-size: 13px; color: #64748b; margin-top: 4px;"><?php esc_html_e('Total Protection Events', 'gexiro-content-protection'); ?></div>
                    </div>
                    <div style="background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; border-left: 4px solid #0284c7;">
                        <div style="font-size: 28px; font-weight: 700; color: #0284c7;"><?php echo esc_html(number_format_i18n($stats['today_blocks'])); ?></div>
                        <div style="font-size: 13px; color: #64748b; margin-top: 4px;"><?php esc_html_e('Today\'s Blocks', 'gexiro-content-protection'); ?></div>
                    </div>
                    <div style="background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; border-left: 4px solid #10b981;">
                        <div style="font-size: 28px; font-weight: 700; color: #059669;"><?php echo esc_html(number_format_i18n($stats['this_week_blocks'])); ?></div>
                        <div style="font-size: 13px; color: #64748b; margin-top: 4px;"><?php esc_html_e('Last 7 Days', 'gexiro-content-protection'); ?></div>
                    </div>
                    <div style="background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; border-left: 4px solid #f59e0b;">
                        <div style="font-size: 28px; font-weight: 700; color: #d97706;"><?php echo esc_html(number_format_i18n($stats['this_month_blocks'])); ?></div>
                        <div style="font-size: 13px; color: #64748b; margin-top: 4px;"><?php esc_html_e('Last 30 Days', 'gexiro-content-protection'); ?></div>
                    </div>
                    <div style="background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center; border-left: 4px solid #dc2626;">
                        <div style="font-size: 28px; font-weight: 700; color: #dc2626;"><?php echo esc_html(number_format_i18n($stats['automated_probes_total'] ?? 0)); ?></div>
                        <div style="font-size: 13px; color: #64748b; margin-top: 4px;"><?php esc_html_e('AI & REST Probes', 'gexiro-content-protection'); ?></div>
                    </div>
                </div>

                <div class="postbox">
                    <h3 class="hndle"><?php esc_html_e('Recent Protection Events', 'gexiro-content-protection'); ?></h3>
                    <div class="inside">
                        <?php if (!empty($recent_events)): ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Event Type', 'gexiro-content-protection'); ?></th>
                                        <th><?php esc_html_e('Page URL', 'gexiro-content-protection'); ?></th>
                                        <th><?php esc_html_e('Timestamp', 'gexiro-content-protection'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_events as $event): ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($event['event_type']); ?></strong></td>
                                            <td><code><?php echo esc_html($event['page_url']); ?></code></td>
                                            <td><?php echo esc_html($event['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="color: #64748b; text-align: center; padding: 20px;"><?php esc_html_e('No protection events recorded yet.', 'gexiro-content-protection'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="postbox">
                    <h3 class="hndle"><?php esc_html_e('Statistics Management', 'gexiro-content-protection'); ?></h3>
                    <div class="inside">
                        <form method="post" action="" style="display: inline;">
                            <?php wp_nonce_field('content_guardian_stats_reset'); ?>
                            <button type="submit" name="reset_stats" class="button button-secondary" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to reset all statistics?', 'gexiro-content-protection')); ?>')" style="background: #dc2626; color: #ffffff; border-color: #b91c1c;">
                                <?php esc_html_e('Reset All Statistics', 'gexiro-content-protection'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}
