<?php
/**
 * Content Guardian (FREE) Settings Manager
 *
 * Handles settings validation, sanitization, and persistence for the FREE edition.
 *
 * @package ContentGuardian
 * @version 3.3.5
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Content_Guardian_Free_Settings_Manager', false)) {

    class Content_Guardian_Free_Settings_Manager {

        private string $option_prefix = 'content_guardian_';

        private array $free_boolean_keys = [
            'disable_right_click',
            'disable_text_selection',
            'disable_copy_paste',
            'disable_drag_drop',
            'admin_bypass',
            'show_alerts',
            'delete_data_on_uninstall',
        ];

        public function __construct() {}

        public function validate_settings(array $input): array {
            $defaults = $this->get_default_settings();
            $sanitized = [];

            foreach ($this->free_boolean_keys as $key) {
                if (array_key_exists($key, $input)) {
                    $sanitized[$key] = !empty($input[$key]) ? 1 : 0;
                }
            }

            if (isset($input['custom_message'])) {
                $msg = sanitize_text_field(wp_unslash((string) $input['custom_message']));
                $sanitized['custom_message'] = $msg !== '' ? $msg : $defaults['custom_message'];
            }

            return $sanitized;
        }

        public function save_settings(array $input): bool {
            if (!is_array($input) || empty($input)) {
                return false;
            }

            $sanitized = $this->validate_settings($input);
            if (empty($sanitized)) {
                return false;
            }

            foreach ($sanitized as $key => $value) {
                update_option($this->option_prefix . $key, $value);
            }

            return true;
        }

        public function get_settings(): array {
            $defaults = $this->get_default_settings();
            $settings = [];
            foreach ($defaults as $key => $default_val) {
                $settings[$key] = get_option($this->option_prefix . $key, $default_val);
            }
            return $settings;
        }

        public function get_default_settings(): array {
            return [
                'disable_right_click' => 1,
                'disable_text_selection' => 1,
                'disable_copy_paste' => 1,
                'disable_drag_drop' => 1,
                'admin_bypass' => 1,
                'show_alerts' => 0,
                'delete_data_on_uninstall' => 0,
                'custom_message' => 'Content is protected!',
            ];
        }
    }
}
