<?php
/**
 * Content Guardian (FREE) Statistics Manager
 *
 * Handles protection statistics, analytics queries, and automatic log retention pruning.
 *
 * @package ContentGuardian
 * @version 3.3.7
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Content_Guardian_Free_Statistics_Manager', false)) {

    class Content_Guardian_Free_Statistics_Manager {

        public function __construct() {
            $this->ensure_table_exists();
            $this->maybe_prune_old_events();
        }

        private function ensure_table_exists(): void {
            if (get_option('content_guardian_db_version') === CONTENT_GUARDIAN_VERSION) {
                return;
            }

            global $wpdb;
            $table_name = $wpdb->prefix . 'content_guardian_statistics';

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name)) !== $table_name) {
                $this->create_statistics_table();
            }

            update_option('content_guardian_db_version', CONTENT_GUARDIAN_VERSION);
        }

        private function create_statistics_table(): void {
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
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            }
            dbDelta($sql);
        }

        public function get_statistics(): array {
            return $this->get_protection_stats();
        }

        public function get_protection_stats(): array {
            global $wpdb;
            $table_name = $wpdb->prefix . 'content_guardian_statistics';

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name)) !== $table_name) {
                return $this->get_empty_stats();
            }

            try {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                $total_blocks = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i', $table_name));

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                $grouped = $wpdb->get_results(
                    $wpdb->prepare('SELECT event_type, COUNT(*) AS cnt FROM %i GROUP BY event_type', $table_name),
                    ARRAY_A
                );
                $counts = [];
                if (is_array($grouped)) {
                    foreach ($grouped as $row) {
                        $counts[$row['event_type']] = (int) $row['cnt'];
                    }
                }

                $now_ts = current_time('timestamp');
                $today = gmdate('Y-m-d', $now_ts);
                $week_ago = gmdate('Y-m-d', $now_ts - (7 * DAY_IN_SECONDS));
                $month_ago = gmdate('Y-m-d', $now_ts - (30 * DAY_IN_SECONDS));

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                $today_blocks = (int) $wpdb->get_var(
                    $wpdb->prepare('SELECT COUNT(*) FROM %i WHERE DATE(created_at) = %s', $table_name, $today)
                );
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                $this_week_blocks = (int) $wpdb->get_var(
                    $wpdb->prepare('SELECT COUNT(*) FROM %i WHERE DATE(created_at) >= %s', $table_name, $week_ago)
                );
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                $this_month_blocks = (int) $wpdb->get_var(
                    $wpdb->prepare('SELECT COUNT(*) FROM %i WHERE DATE(created_at) >= %s', $table_name, $month_ago)
                );

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                $top_pages_raw = $wpdb->get_results(
                    $wpdb->prepare('SELECT page_url, COUNT(*) as count FROM %i GROUP BY page_url ORDER BY count DESC LIMIT 5', $table_name)
                );
                $top_protected_pages = [];
                if ($top_pages_raw) {
                    foreach ($top_pages_raw as $page) {
                        $top_protected_pages[$page->page_url] = (int) $page->count;
                    }
                }

                return [
                    'total_blocks' => $total_blocks,
                    'right_clicks_blocked' => $counts['right_click'] ?? 0,
                    'copy_attempts_blocked' => $counts['copy_attempt'] ?? 0,
                    'f12_blocks' => $counts['f12_block'] ?? 0,
                    'print_screen_blocks' => $counts['print_screen'] ?? 0,
                    'view_source_blocked' => $counts['view_source_block'] ?? 0,
                    'save_blocks' => $counts['save_block'] ?? 0,
                    'print_blocks' => $counts['print_block'] ?? 0,
                    'drag_attempts' => $counts['drag_attempt'] ?? 0,
                    'devtools_detections' => $counts['devtools_detected'] ?? 0,
                    'today_blocks' => $today_blocks,
                    'this_week_blocks' => $this_week_blocks,
                    'this_month_blocks' => $this_month_blocks,
                    'rest_scrape_probes' => $counts['rest_scrape_probe'] ?? 0,
                    'ai_bot_probes' => $counts['ai_bot_probe'] ?? 0,
                    'automated_probes_total' => ($counts['rest_scrape_probe'] ?? 0) + ($counts['ai_bot_probe'] ?? 0),
                    'top_protected_pages' => $top_protected_pages
                ];
            } catch (\Throwable $e) {
                return $this->get_empty_stats();
            }
        }

        private function get_empty_stats(): array {
            return [
                'total_blocks' => 0,
                'right_clicks_blocked' => 0,
                'copy_attempts_blocked' => 0,
                'f12_blocks' => 0,
                'print_screen_blocks' => 0,
                'view_source_blocked' => 0,
                'save_blocks' => 0,
                'print_blocks' => 0,
                'drag_attempts' => 0,
                'devtools_detections' => 0,
                'today_blocks' => 0,
                'this_week_blocks' => 0,
                'this_month_blocks' => 0,
                'rest_scrape_probes' => 0,
                'ai_bot_probes' => 0,
                'automated_probes_total' => 0,
                'top_protected_pages' => []
            ];
        }

        public function get_recent_events(int $limit = 10): array {
            global $wpdb;
            $table_name = $wpdb->prefix . 'content_guardian_statistics';

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name)) !== $table_name) {
                return [];
            }

            try {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                $results = $wpdb->get_results($wpdb->prepare(
                    'SELECT event_type, page_url, user_agent, ip_address, created_at FROM %i ORDER BY created_at DESC, id DESC LIMIT %d',
                    $table_name,
                    max(1, min(100, $limit))
                ));

                if (!$results) {
                    return [];
                }

                $events = [];
                foreach ($results as $row) {
                    $events[] = [
                        'event_type' => $row->event_type,
                        'page_url' => $row->page_url,
                        'user_agent' => $row->user_agent,
                        'ip_address' => $row->ip_address,
                        'created_at' => $row->created_at
                    ];
                }

                return $events;
            } catch (\Throwable $e) {
                return [];
            }
        }

        public function reset_statistics(string $type = 'all'): bool {
            global $wpdb;
            $table_name = $wpdb->prefix . 'content_guardian_statistics';

            try {
                if ($type === 'all') {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->query($wpdb->prepare('DELETE FROM %i WHERE 1=1', $table_name));
                    update_option('content_guardian_total_blocks', 0);
                    update_option('content_guardian_last_reset', current_time('mysql'));
                    delete_option('content_guardian_reset_confirm');
                } elseif ($type === 'today') {
                    $today = current_time('Y-m-d');
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->query($wpdb->prepare('DELETE FROM %i WHERE DATE(created_at) = %s', $table_name, $today));
                }
            } catch (\Throwable $e) {
                return false;
            }

            return true;
        }

        private function maybe_prune_old_events(int $retention_days = 90): void {
            $last_prune = (int) get_option('content_guardian_last_prune', 0);
            if ((time() - $last_prune) < DAY_IN_SECONDS) {
                return;
            }

            update_option('content_guardian_last_prune', time(), 'no');

            global $wpdb;
            $table_name = $wpdb->prefix . 'content_guardian_statistics';
            $cutoff = gmdate('Y-m-d H:i:s', time() - ($retention_days * DAY_IN_SECONDS));
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query($wpdb->prepare('DELETE FROM %i WHERE created_at < %s', $table_name, $cutoff));

            // Hard row-cap (50,000 events max) to prevent unbounded database growth on high-traffic sites.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $threshold_id = $wpdb->get_var($wpdb->prepare('SELECT id FROM %i ORDER BY id DESC LIMIT 1 OFFSET 50000', $table_name));
            if ($threshold_id !== null) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->query($wpdb->prepare('DELETE FROM %i WHERE id <= %d', $table_name, (int) $threshold_id));
            }
        }


        public function record_passive_probe(string $event_type, string $request_path): void {
            $allowed = ['rest_scrape_probe', 'ai_bot_probe'];
            if (!in_array($event_type, $allowed, true)) {
                return;
            }

            $this->ensure_table_exists();

            global $wpdb;
            $table_name = $wpdb->prefix . 'content_guardian_statistics';
            $clean_path = substr(sanitize_text_field($request_path), 0, 250);
            if ($clean_path === '') {
                $clean_path = '/';
            }
            $ua = isset($_SERVER['HTTP_USER_AGENT'])
                ? substr(sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_USER_AGENT'])), 0, 255)
                : '';
            $raw_ip = isset($_SERVER['REMOTE_ADDR'])
                ? sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR']))
                : '';
            $anon_ip = function_exists('wp_privacy_anonymize_ip')
                ? (string) wp_privacy_anonymize_ip($raw_ip)
                : '0.0.0.0';

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->insert(
                $table_name,
                [
                    'event_type' => $event_type,
                    'page_url' => $clean_path,
                    'user_agent' => $ua,
                    'ip_address' => substr($anon_ip, 0, 45),
                ],
                ['%s', '%s', '%s', '%s']
            );
        }

        public function export_statistics(): array {
            return $this->get_protection_stats();
        }
    }
}
