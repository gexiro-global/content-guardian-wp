<?php
/**
 * Content Guardian (FREE) Protection Engine
 *
 * Handles the 4 core frontend protection layers (Right-Click, Text Selection, Copy/Cut & Clipboard,
 * and Image Drag & Save Shield) using native WordPress asset enqueueing, privacy-first IP anonymization,
 * and PHP2Shell-hardened telemetry.
 *
 * @package ContentGuardian
 * @version 3.3.5
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Content_Guardian_Free_Protection_Engine', false)) {

    class Content_Guardian_Free_Protection_Engine {

        private array $options = [];

        private const ALLOWED_EVENTS = [
            'right_click',
            'copy_attempt',
            'drag_attempt',
        ];

        public function __construct() {
            $this->options = $this->get_protection_settings();

            add_action('init', [$this, 'refresh_options']);
            add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_protection'], 20);

            add_action('wp_ajax_nopriv_content_guardian_log_event', [$this, 'log_protection_event']);
            add_action('wp_ajax_content_guardian_log_event', [$this, 'log_protection_event']);
        }

        public function refresh_options(): void {
            wp_cache_delete('content_guardian_disable_right_click', 'options');
            $this->options = $this->get_protection_settings();
        }

        private function get_protection_settings(): array {
            return [
                'disable_right_click' => (int) get_option('content_guardian_disable_right_click', 1),
                'disable_text_selection' => (int) get_option('content_guardian_disable_text_selection', 1),
                'disable_copy_paste' => (int) get_option('content_guardian_disable_copy_paste', 1),
                'disable_drag_drop' => (int) get_option('content_guardian_disable_drag_drop', 1),
                'show_alerts' => (int) get_option('content_guardian_show_alerts', 0),
                'custom_message' => (string) get_option('content_guardian_custom_message', 'Content is protected!'),
                'admin_bypass' => (int) get_option('content_guardian_admin_bypass', 1),
            ];
        }

        private function is_feature_available(string $feature_name): bool {
            $free_features = [
                'disable_right_click',
                'disable_text_selection',
                'disable_copy_paste',
                'disable_drag_drop',
            ];
            return in_array($feature_name, $free_features, true);
        }

        public function enqueue_frontend_protection(): void {
            if (is_admin()) {
                return;
            }

            if (empty($this->options)) {
                $this->options = $this->get_protection_settings();
            }

            if ($this->options['admin_bypass'] && is_user_logged_in() && current_user_can('manage_options')) {
                return;
            }

            $css_rules = [];
            if ($this->options['disable_text_selection'] && $this->is_feature_available('disable_text_selection')) {
                $css_rules[] = 'body, body *:not(input):not(textarea):not([contenteditable="true"]) { -webkit-user-select: none !important; -moz-user-select: none !important; -ms-user-select: none !important; user-select: none !important; -webkit-touch-callout: none !important; }';
                $css_rules[] = 'input, textarea, [contenteditable="true"] { -webkit-user-select: text !important; -moz-user-select: text !important; user-select: text !important; }';
                $css_rules[] = '::selection { background: transparent !important; } ::-moz-selection { background: transparent !important; }';
            }
            if ($this->options['disable_drag_drop'] && $this->is_feature_available('disable_drag_drop')) {
                $css_rules[] = 'img, picture, svg { -webkit-user-drag: none !important; user-drag: none !important; }';
            }

            if (!empty($css_rules)) {
                wp_register_style('content-guardian-frontend', false, [], CONTENT_GUARDIAN_VERSION);
                wp_enqueue_style('content-guardian-frontend');
                wp_add_inline_style('content-guardian-frontend', implode("\n", $css_rules));
            }

            $any_js = ($this->options['disable_right_click'] && $this->is_feature_available('disable_right_click'))
                || ($this->options['disable_copy_paste'] && $this->is_feature_available('disable_copy_paste'))
                || ($this->options['disable_drag_drop'] && $this->is_feature_available('disable_drag_drop'));

            if (!$any_js) {
                return;
            }

            $config_json = wp_json_encode([
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('content_guardian_telemetry_nonce'),
                'showAlerts' => (bool) $this->options['show_alerts'],
                'message' => $this->options['custom_message'],
                'rightClick' => (bool) $this->options['disable_right_click'],
                'copyPaste' => (bool) $this->options['disable_copy_paste'],
                'dragDrop' => (bool) $this->options['disable_drag_drop'],
            ]);

            $inline_js = implode(
                "\n",
                [
                    '(function() {',
                    "    'use strict';",
                    '    var cfg = ' . $config_json . ';',
                    '    if (!cfg) { return; }',
                    '    function isEditableTarget(el) {',
                    '        if (!el) { return false; }',
                    "        var tag = (el.tagName || '').toUpperCase();",
                    "        return tag === 'INPUT' || tag === 'TEXTAREA' || el.isContentEditable === true;",
                    '    }',
                    '    function logEvent(eventType) {',
                    '        var payload = new URLSearchParams();',
                    "        payload.set('action', 'content_guardian_log_event');",
                    "        payload.set('nonce', cfg.nonce);",
                    "        payload.set('event_type', eventType);",
                    "        payload.set('page_url', window.location.pathname);",
                    '        fetch(cfg.ajaxUrl, {',
                    "            method: 'POST',",
                    "            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },",
                    '            body: payload.toString(),',
                    "            credentials: 'same-origin'",
                    '        }).catch(function() {});',
                    '    }',
                    '    if (cfg.rightClick) {',
                    "        document.addEventListener('contextmenu', function(e) {",
                    '            e.preventDefault();',
                    '            e.stopPropagation();',
                    "            logEvent('right_click');",
                    '            if (cfg.showAlerts) { window.alert(cfg.message); }',
                    '            return false;',
                    '        }, true);',
                    '    }',
                    '    if (cfg.copyPaste) {',
                    "        ['copy', 'cut'].forEach(function(evtName) {",
                    '            document.addEventListener(evtName, function(e) {',
                    '                if (isEditableTarget(e.target)) { return; }',
                    '                e.preventDefault();',
                    '                e.stopPropagation();',
                    "                logEvent('copy_attempt');",
                    '                if (cfg.showAlerts) { window.alert(cfg.message); }',
                    '                return false;',
                    '            }, true);',
                    '        });',
                    "        document.addEventListener('keydown', function(e) {",
                    "            var key = (e.key || '').toLowerCase();",
                    '            var mod = e.ctrlKey || e.metaKey;',
                    "            if (mod && (key === 'c' || key === 'v' || key === 'a' || key === 'x') && !isEditableTarget(e.target)) {",
                    '                e.preventDefault();',
                    '                e.stopPropagation();',
                    "                logEvent('copy_attempt');",
                    '                if (cfg.showAlerts) { window.alert(cfg.message); }',
                    '                return false;',
                    '            }',
                    '        }, true);',
                    '    }',
                    '    if (cfg.dragDrop) {',
                    "        document.addEventListener('dragstart', function(e) {",
                    '            var target = e.target;',
                    "            var tag = target && target.tagName ? target.tagName.toUpperCase() : '';",
                    "            if (tag === 'IMG' || tag === 'PICTURE' || tag === 'SVG' || (target && target.closest && target.closest('picture, figure'))) {",
                    '                e.preventDefault();',
                    '                e.stopPropagation();',
                    "                logEvent('drag_attempt');",
                    '                return false;',
                    '            }',
                    '        }, true);',
                    '    }',
                    '})();',
                ]
            );

            wp_register_script('content-guardian-frontend', false, [], CONTENT_GUARDIAN_VERSION, true);
            wp_enqueue_script('content-guardian-frontend');
            wp_add_inline_script('content-guardian-frontend', $inline_js);
        }

        public function log_protection_event(): void {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw header inspected for NUL-byte and multipart type before sanitization.
            $raw_content_type = isset($_SERVER['CONTENT_TYPE']) ? (string) wp_unslash($_SERVER['CONTENT_TYPE']) : '';
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw header inspected for NUL-byte before sanitization.
            $raw_ua_check = isset($_SERVER['HTTP_USER_AGENT']) ? (string) wp_unslash($_SERVER['HTTP_USER_AGENT']) : '';
            if (strpos($raw_content_type, "\0") !== false || strpos($raw_ua_check, "\0") !== false) {
                wp_send_json_error(['message' => 'Malformed input rejected'], 400);
                return;
            }

            $content_type = strtolower(sanitize_text_field($raw_content_type));
            if (strpos($content_type, 'multipart/form-data') !== false) {
                wp_send_json_error(['message' => 'Multipart requests are not accepted on telemetry endpoints'], 415);
                return;
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Pre-nonce NUL-byte inspection across all POST keys and values.
            foreach ($_POST as $post_key => $post_val) {
                if ((is_string($post_key) && strpos($post_key, "\0") !== false)
                    || (is_string($post_val) && strpos($post_val, "\0") !== false)
                    || is_array($post_val)) {
                    wp_send_json_error(['message' => 'Malformed input rejected'], 400);
                    return;
                }
            }

            $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
            if (!wp_verify_nonce($nonce, 'content_guardian_telemetry_nonce')) {
                wp_send_json_error(['message' => 'Security check failed'], 403);
                return;
            }

            $event_type = isset($_POST['event_type']) ? sanitize_key(wp_unslash($_POST['event_type'])) : '';
            if (!in_array($event_type, self::ALLOWED_EVENTS, true)) {
                wp_send_json_error(['message' => 'Invalid event type'], 400);
                return;
            }

            $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? trim(sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))) : '';
            $valid_ip = filter_var($remote_addr, FILTER_VALIDATE_IP) ? $remote_addr : '0.0.0.0';

            $minute_bucket = (int) floor(time() / 60);
            $rate_key = 'cg_rl_' . $minute_bucket . '_' . substr(hash('sha256', $valid_ip . wp_salt('auth')), 0, 12);
            $count = (int) get_transient($rate_key);
            if ($count >= 30) {
                wp_send_json_error(['message' => 'Rate limit exceeded'], 429);
                return;
            }
            set_transient($rate_key, $count + 1, 70);

            $stored_ip = function_exists('wp_privacy_anonymize_ip')
                ? wp_privacy_anonymize_ip($valid_ip)
                : '0.0.0.0';

            $raw_url = isset($_POST['page_url']) ? sanitize_text_field(wp_unslash($_POST['page_url'])) : '/';
            $parsed_path = wp_parse_url($raw_url, PHP_URL_PATH);
            $clean_path = (is_string($parsed_path) && $parsed_path !== '') ? '/' . ltrim($parsed_path, '/') : '/';
            $clean_path = str_replace(['<', '>', '"', "'"], '', $clean_path);
            $page_url = function_exists('mb_substr') ? mb_substr($clean_path, 0, 250) : substr($clean_path, 0, 250);

            $raw_ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
            $user_agent = function_exists('mb_substr') ? mb_substr($raw_ua, 0, 500) : substr($raw_ua, 0, 500);

            global $wpdb;
            $table_name = $wpdb->prefix . 'content_guardian_statistics';

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom plugin telemetry table.
            $result = $wpdb->insert(
                $table_name,
                [
                    'event_type' => $event_type,
                    'page_url' => $page_url,
                    'user_agent' => $user_agent,
                    'ip_address' => $stored_ip,
                    'created_at' => current_time('mysql')
                ],
                ['%s', '%s', '%s', '%s', '%s']
            );

            if ($result === false) {
                wp_send_json_error(['message' => 'Failed to log event'], 500);
                return;
            }

            wp_send_json_success(['logged' => true]);
        }

        public function get_pro_status(): bool {
            return false;
        }
    }
}
