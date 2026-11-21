<?php
/**
 * Plugin Name: Gexiro Content Protection
 * Plugin URI: https://gexiro.com/en/products/content-guardian-pro
 * Description: Lightweight, zero-dependency WordPress & WooCommerce content protection with browser copy shield and live REST API / AI crawler exposure audit. Upgrade to PRO for 21 full-stack protection modules.
 * Version: 3.3.7
 * Author: Gexiro
 * Author URI: https://gexiro.com
 * Text Domain: gexiro-content-protection
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CONTENT_GUARDIAN_VERSION')) {
    define('CONTENT_GUARDIAN_VERSION', '3.3.7');
}
if (!defined('CONTENT_GUARDIAN_PLUGIN_URL')) {
    define('CONTENT_GUARDIAN_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('CONTENT_GUARDIAN_PLUGIN_PATH')) {
    define('CONTENT_GUARDIAN_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('CONTENT_GUARDIAN_PLUGIN_FILE')) {
    define('CONTENT_GUARDIAN_PLUGIN_FILE', __FILE__);
}

if (!defined('CONTENT_GUARDIAN_PRO_UPGRADE_URL')) {
    define('CONTENT_GUARDIAN_PRO_UPGRADE_URL', 'https://gexiro.com/en/products/content-guardian-pro');
}

if (!class_exists('Content_Guardian_Free_Admin', false)) {

    /**
     * Main Content Guardian (FREE) Controller
     *
     * Automatically yields to Content Guardian Pro on `plugins_loaded` if both plugins
     * are present simultaneously during an upgrade, preventing duplicate hooks or class collisions.
     */
    class Content_Guardian_Free_Admin {

        private $protection_engine = null;
        private $statistics_manager = null;
        private $settings_manager = null;

        public function __construct() {
            register_activation_hook(__FILE__, [$this, 'activate']);
            register_deactivation_hook(__FILE__, [$this, 'deactivate']);

            add_action('plugins_loaded', [$this, 'boot_if_standalone'], 5);
        }

        private function is_pro_present(): bool {
            return defined('CONTENT_GUARDIAN_PRO_VERSION') || class_exists('Content_Guardian_Pro_Admin', false);
        }

        public function boot_if_standalone(): void {
            if ($this->is_pro_present()) {
                return;
            }

            add_filter('locale', [$this, 'override_locale']);

            $this->load_dependencies();
            $this->init_components();

            if (is_admin() && get_option('content_guardian_activated_at', false) === false) {
                add_option('content_guardian_activated_at', time(), '', false);
            }

            add_action('admin_init', [$this, 'register_privacy_policy_content']);
            add_action('admin_init', [$this, 'handle_audit_notice_dismiss']);
            add_action('admin_init', [$this, 'handle_review_notice_dismiss']);
            add_action('admin_notices', [$this, 'render_activation_exposure_notice']);
            add_action('admin_notices', [$this, 'render_review_request_notice']);
            add_action('admin_menu', [$this, 'add_admin_menu']);
            add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_plugin_action_links']);
            add_action('rest_api_init', [$this, 'register_passive_rest_observer']);
            add_action('template_redirect', [$this, 'observe_passive_ai_crawler']);

            add_action('wp_ajax_content_guardian_save_settings', [$this, 'ajax_save_settings']);
            add_action('wp_ajax_content_guardian_reset_stats', [$this, 'ajax_reset_statistics']);
            add_action('wp_ajax_content_guardian_get_stats', [$this, 'ajax_get_statistics']);
        }

        public function register_privacy_policy_content(): void {
            if (!function_exists('wp_add_privacy_policy_content')) {
                return;
            }

            $content = sprintf(
                '<h2>%s</h2><p>%s</p><p>%s</p>',
                esc_html__('Gexiro Content Protection — Privacy & Telemetry Notice', 'gexiro-content-protection'),
                esc_html__('Gexiro Content Protection protects published website content against unauthorized copying and automated scraping locally on your WordPress server and sends zero visitor data to external servers.', 'gexiro-content-protection'),
                esc_html__('When a protected browser interaction (such as right-click, clipboard copy, or image drag) is blocked, an anonymized event record (event type, page path, browser user-agent, and IP address anonymized via WordPress core wp_privacy_anonymize_ip) is stored locally for aggregate security statistics and automatically pruned after 90 days.', 'gexiro-content-protection')
            );

            wp_add_privacy_policy_content(
                esc_html__('Gexiro Content Protection', 'gexiro-content-protection'),
                wp_kses_post($content)
            );
        }

        private function load_dependencies(): void {
            require_once CONTENT_GUARDIAN_PLUGIN_PATH . 'includes/class-protection-engine.php';
            require_once CONTENT_GUARDIAN_PLUGIN_PATH . 'includes/class-statistics-manager.php';
            require_once CONTENT_GUARDIAN_PLUGIN_PATH . 'includes/class-settings-manager.php';
            require_once CONTENT_GUARDIAN_PLUGIN_PATH . 'admin/class-admin-page.php';
        }

        private function init_components(): void {
            $this->protection_engine = new Content_Guardian_Free_Protection_Engine();
            $this->statistics_manager = new Content_Guardian_Free_Statistics_Manager();
            $this->settings_manager = new Content_Guardian_Free_Settings_Manager();
        }

        public function add_admin_menu(): void {
            add_menu_page(
                esc_html__('Gexiro Content Protection', 'gexiro-content-protection'),
                esc_html__('Gexiro Content Protection', 'gexiro-content-protection'),
                'manage_options',
                'content-guardian-dashboard',
                [$this, 'admin_page_callback'],
                'dashicons-shield-alt'
            );

            add_submenu_page(
                'content-guardian-dashboard',
                esc_html__('Dashboard', 'gexiro-content-protection'),
                esc_html__('Dashboard', 'gexiro-content-protection'),
                'manage_options',
                'content-guardian-dashboard',
                [$this, 'admin_page_callback']
            );

            add_submenu_page(
                'content-guardian-dashboard',
                esc_html__('Protection Settings', 'gexiro-content-protection'),
                esc_html__('Protection Settings', 'gexiro-content-protection'),
                'manage_options',
                'content-guardian-settings',
                [$this, 'settings_page_callback']
            );

            add_submenu_page(
                'content-guardian-dashboard',
                esc_html__('Statistics', 'gexiro-content-protection'),
                esc_html__('Statistics', 'gexiro-content-protection'),
                'manage_options',
                'content-guardian-statistics',
                [$this, 'statistics_page_callback']
            );
        }

        public function admin_enqueue_scripts($hook): void {
            if (strpos((string) $hook, 'content-guardian') === false) {
                return;
            }

            wp_enqueue_style(
                'content-guardian-admin',
                CONTENT_GUARDIAN_PLUGIN_URL . 'assets/css/admin.css',
                [],
                CONTENT_GUARDIAN_VERSION
            );

            wp_enqueue_script(
                'content-guardian-admin',
                CONTENT_GUARDIAN_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                CONTENT_GUARDIAN_VERSION,
                true
            );

            wp_localize_script('content-guardian-admin', 'contentGuardian', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('content_guardian_nonce'),
                'i18n' => [
                    'calculating' => esc_html__('Processing...', 'gexiro-content-protection'),
                    'error' => esc_html__('Error', 'gexiro-content-protection'),
                    'success' => esc_html__('Success', 'gexiro-content-protection'),
                ]
            ]);
        }

        public function admin_page_callback(): void {
            $admin_page = new Content_Guardian_Free_Admin_Page();
            $admin_page->render_dashboard();
        }

        public function settings_page_callback(): void {
            $admin_page = new Content_Guardian_Free_Admin_Page();
            $admin_page->render_settings_page();
        }

        public function statistics_page_callback(): void {
            $admin_page = new Content_Guardian_Free_Admin_Page();
            $admin_page->render_statistics_page();
        }

        public function add_plugin_action_links(array $links): array {
            $settings_link = sprintf(
                '<a href="%s">%s</a>',
                esc_url(admin_url('admin.php?page=content-guardian-dashboard')),
                esc_html__('Dashboard', 'gexiro-content-protection')
            );
            $upgrade_link = sprintf(
                '<a href="%s" target="_blank" rel="noopener" style="color:#059669;font-weight:600;">%s</a>',
                esc_url(CONTENT_GUARDIAN_PRO_UPGRADE_URL . '?utm_source=wp-plugin&utm_medium=plugins-row&utm_campaign=v337#pricing'),
                esc_html__('Get Pro (-25% LAUNCH25)', 'gexiro-content-protection')
            );
            array_unshift($links, $settings_link, $upgrade_link);
            return $links;
        }


        public function register_passive_rest_observer(): void {
            add_filter('rest_pre_dispatch', [$this, 'observe_rest_request'], 10, 3);
        }

        public function observe_rest_request($result, $server, $request) {
            if ((function_exists('is_user_logged_in') && is_user_logged_in()) || !is_object($request) || !method_exists($request, 'get_route')) {
                return $result;
            }
            $method = strtoupper(isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_METHOD'])) : 'GET');
            if ($method !== 'GET') {
                return $result;
            }
            $route = (string) $request->get_route();
            if (
                strpos($route, '/wp/v2/posts') === 0
                || strpos($route, '/wp/v2/pages') === 0
                || strpos($route, '/wc/store/') === 0
            ) {
                if (!get_transient('cg_free_rest_probe_lock')) {
                    set_transient('cg_free_rest_probe_lock', 1, 10);
                    $stats = $this->statistics_manager ?: new Content_Guardian_Free_Statistics_Manager();
                    $stats->record_passive_probe('rest_scrape_probe', $route);
                }
            }
            return $result;
        }

        public function observe_passive_ai_crawler(): void {
            if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
                return;
            }
            if (function_exists('is_user_logged_in') && is_user_logged_in()) {
                return;
            }
            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_USER_AGENT'])) : '';
            if ($ua !== '' && preg_match('/(gptbot|claudebot|anthropic-ai|ccbot|bytespider|perplexitybot|google-extended|applebot-extended|python-requests|scrapy|go-http-client|headlesschrome|curl\/|wget\/)/i', $ua)) {
                if (!get_transient('cg_free_ai_probe_lock')) {
                    set_transient('cg_free_ai_probe_lock', 1, 10);
                    $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_URI'])) : '/';
                    $stats = $this->statistics_manager ?: new Content_Guardian_Free_Statistics_Manager();
                    $stats->record_passive_probe('ai_bot_probe', $uri);
                }
            }
        }

        public function handle_audit_notice_dismiss(): void {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified immediately inside condition.
            if (
                isset($_GET['cg_dismiss_audit_notice'], $_GET['_wpnonce'])
                && current_user_can('manage_options')
                && wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_GET['_wpnonce'])), 'cg_dismiss_audit_notice')
            ) {
                update_option('content_guardian_audit_notice_dismissed', 1, false);
                wp_safe_redirect(remove_query_arg(['cg_dismiss_audit_notice', '_wpnonce']));
                exit;
            }
        }

        public function handle_review_notice_dismiss(): void {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified immediately inside condition.
            if (
                isset($_GET['cg_dismiss_review_notice'], $_GET['_wpnonce'])
                && current_user_can('manage_options')
                && wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_GET['_wpnonce'])), 'cg_dismiss_review_notice')
            ) {
                update_option('content_guardian_review_dismissed', 1, false);
                wp_safe_redirect(remove_query_arg(['cg_dismiss_review_notice', '_wpnonce']));
                exit;
            }
        }

        public function render_activation_exposure_notice(): void {
            if (!current_user_can('manage_options')) {
                return;
            }
            if ((int) get_option('content_guardian_audit_notice_dismissed', 0) === 1) {
                return;
            }
            $screen = function_exists('get_current_screen') ? get_current_screen() : null;
            if (!$screen || !in_array($screen->id, ['plugins', 'dashboard'], true)) {
                return;
            }
            $post_counts = wp_count_posts('post');
            $posts_pub = isset($post_counts->publish) ? (int) $post_counts->publish : 0;
            $audit_url = admin_url('admin.php?page=content-guardian-dashboard');
            $pro_url = CONTENT_GUARDIAN_PRO_UPGRADE_URL . '?utm_source=wp-plugin&utm_medium=activation-notice&utm_campaign=v337#pricing';
            $dismiss_url = wp_nonce_url(add_query_arg('cg_dismiss_audit_notice', '1'), 'cg_dismiss_audit_notice');
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php esc_html_e('Gexiro Content Protection (Browser Copy-Lock Active):', 'gexiro-content-protection'); ?></strong>
                    <?php
                    printf(
                        /* translators: %d: number of published posts exposed via REST API */
                        esc_html__('Right-click and copy shortcuts are shielded for browsers, while %d published posts remain readable in full HTML at /wp-json/wp/v2/posts to automated scrapers and AI crawlers.', 'gexiro-content-protection'),
                        (int) $posts_pub
                    );
                    ?>
                </p>
                <p>
                    <a href="<?php echo esc_url($audit_url); ?>" class="button button-secondary"><?php esc_html_e('Open Site Exposure Audit', 'gexiro-content-protection'); ?></a>
                    <a href="<?php echo esc_url($pro_url); ?>" target="_blank" rel="noopener" class="button button-primary" style="background:#059669;border-color:#047857;margin-left:6px;"><?php esc_html_e('Unlock REST/RSS & AI Shield in PRO (-25% Code: LAUNCH25)', 'gexiro-content-protection'); ?></a>
                    <a href="<?php echo esc_url($dismiss_url); ?>" style="margin-left:12px;"><?php esc_html_e('Dismiss', 'gexiro-content-protection'); ?></a>
                </p>
            </div>
            <?php
        }

        public function render_review_request_notice(): void {
            if (!current_user_can('manage_options')) {
                return;
            }
            if ((int) get_option('content_guardian_review_dismissed', 0) === 1) {
                return;
            }
            $screen = function_exists('get_current_screen') ? get_current_screen() : null;
            if (!$screen || strpos((string) $screen->id, 'content-guardian') === false) {
                return;
            }
            $activated_at = (int) get_option('content_guardian_activated_at', 0);
            $days_active = $activated_at > 0 ? (time() - $activated_at) / 86400 : 0;
            $stats = $this->statistics_manager ? $this->statistics_manager->get_protection_stats() : [];
            $total_blocks = (int) ($stats['total_blocks'] ?? 0);
            if ($days_active < 7 && $total_blocks < 10) {
                return;
            }
            $review_url = 'https://wordpress.org/support/plugin/gexiro-content-protection/reviews/#new-post';
            $dismiss_url = wp_nonce_url(add_query_arg('cg_dismiss_review_notice', '1'), 'cg_dismiss_review_notice');
            ?>
            <div class="notice notice-info">
                <p>
                    <strong><?php esc_html_e('Enjoying Content Guardian (Gexiro Content Protection)?', 'gexiro-content-protection'); ?></strong>
                    <?php esc_html_e('If the local copy shield and Site Exposure Audit are helping protect your WordPress or WooCommerce site, a quick 5-star review on WordPress.org helps independent security development thrive.', 'gexiro-content-protection'); ?>
                </p>
                <p>
                    <a href="<?php echo esc_url($review_url); ?>" target="_blank" rel="noopener" class="button button-primary"><?php esc_html_e('Leave a 5-Star Review on WordPress.org', 'gexiro-content-protection'); ?></a>
                    <a href="<?php echo esc_url($dismiss_url); ?>" class="button button-secondary" style="margin-left:8px;"><?php esc_html_e('Already reviewed / Dismiss', 'gexiro-content-protection'); ?></a>
                </p>
            </div>
            <?php
        }

        public function override_locale($locale) {
            if (!is_admin()) {
                return $locale;
            }
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin screen routing check.
            $admin_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
            if ($admin_page !== '' && strpos($admin_page, 'content-guardian') === 0) {
                $allowed_locales = ['auto', 'en_US', 'de_DE', 'es_ES', 'fr_FR', 'it_IT', 'ja', 'nl_NL', 'pl_PL', 'pt_BR'];
                $plugin_language = (string) get_option('content_guardian_interface_language', 'en_US');
                if ($plugin_language !== 'auto' && in_array($plugin_language, $allowed_locales, true)) {
                    return $plugin_language;
                }
            }
            return $locale;
        }

        public function activate(): void {
            $this->create_tables();
            $this->set_default_options();
            flush_rewrite_rules();
        }

        public function deactivate(): void {
            flush_rewrite_rules();
        }

        private function create_tables(): void {
            global $wpdb;
            $safe_prefix = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $wpdb->prefix);
            $table_name = $safe_prefix . 'content_guardian_statistics';
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                event_type varchar(50) NOT NULL,
                page_url varchar(255) NOT NULL,
                user_agent text,
                ip_address varchar(45),
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY event_type (event_type),
                KEY page_url (page_url),
                KEY created_at (created_at)
            ) $charset_collate;";

            if (!function_exists('dbDelta')) {
                $upgrade_inc = ABSPATH . 'wp-admin/includes/upgrade.php';
                if (is_file($upgrade_inc)) {
                    require_once $upgrade_inc;
                }
            }
            if (function_exists('dbDelta')) {
                dbDelta($sql);
                update_option('content_guardian_db_version', CONTENT_GUARDIAN_VERSION);
            }
        }

        private function set_default_options(): void {
            $defaults = [
                'content_guardian_admin_bypass' => 1,
                'content_guardian_show_alerts' => 0,
                'content_guardian_delete_data_on_uninstall' => 0,
                'content_guardian_interface_language' => 'en_US',
                'content_guardian_custom_message' => 'Content is protected!',
                'content_guardian_disable_right_click' => 1,
                'content_guardian_disable_text_selection' => 1,
                'content_guardian_disable_copy_paste' => 1,
                'content_guardian_disable_drag_drop' => 1,
                'content_guardian_activated_at' => time(),
            ];

            foreach ($defaults as $option => $value) {
                if (get_option($option, null) === null) {
                    add_option($option, $value);
                }
            }
        }

        public function ajax_save_settings(): void {
            $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
            if (!wp_verify_nonce($nonce, 'content_guardian_nonce')) {
                wp_send_json_error(['message' => esc_html__('Security check failed', 'gexiro-content-protection')], 403);
            }

            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'gexiro-content-protection')], 403);
            }

            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized key-by-key inside save_settings().
            $raw_settings = isset($_POST['settings']) ? wp_unslash($_POST['settings']) : [];
            $settings = is_array($raw_settings) ? $raw_settings : [];

            if ($this->settings_manager && $this->settings_manager->save_settings($settings)) {
                wp_send_json_success(['message' => esc_html__('Settings saved successfully!', 'gexiro-content-protection')]);
            }

            wp_send_json_error(['message' => esc_html__('Failed to save settings', 'gexiro-content-protection')]);
        }

        public function ajax_reset_statistics(): void {
            $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
            if (!wp_verify_nonce($nonce, 'content_guardian_nonce')) {
                wp_send_json_error(['message' => esc_html__('Security check failed', 'gexiro-content-protection')], 403);
            }

            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'gexiro-content-protection')], 403);
            }

            if ($this->statistics_manager && $this->statistics_manager->reset_statistics()) {
                wp_send_json_success(['message' => esc_html__('Statistics reset successfully!', 'gexiro-content-protection')]);
            }

            wp_send_json_error(['message' => esc_html__('Failed to reset statistics', 'gexiro-content-protection')]);
        }

        public function ajax_get_statistics(): void {
            $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
            if (!wp_verify_nonce($nonce, 'content_guardian_nonce')) {
                wp_send_json_error(['message' => esc_html__('Security check failed', 'gexiro-content-protection')], 403);
            }

            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'gexiro-content-protection')], 403);
            }

            if ($this->statistics_manager) {
                $stats = $this->statistics_manager->get_statistics();
                wp_send_json_success($stats);
            }

            wp_send_json_error(['message' => esc_html__('Failed to get statistics', 'gexiro-content-protection')]);
        }
    }
}

new Content_Guardian_Free_Admin();
