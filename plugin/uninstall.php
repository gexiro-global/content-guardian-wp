<?php
/**
 * Gexiro Content Protection (FREE) — Clean Opt-In Uninstall Handler
 *
 * Preserves settings by default when upgrading from FREE to Content Guardian Pro,
 * and never deletes shared tables/options if Content Guardian Pro is installed.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if ((int) get_option('content_guardian_delete_data_on_uninstall', 0) !== 1) {
    return;
}

// Never wipe shared telemetry or settings if Content Guardian Pro is installed or active alongside Free.
$content_guardian_active_plugins = (array) get_option('active_plugins', []);
$content_guardian_pro_in_active  = in_array('content-guardian-pro/content-guardian-pro.php', $content_guardian_active_plugins, true);
if (!$content_guardian_pro_in_active) {
    foreach ($content_guardian_active_plugins as $content_guardian_plugin_slug) {
        if (is_string($content_guardian_plugin_slug) && strpos($content_guardian_plugin_slug, 'content-guardian-pro/') === 0) {
            $content_guardian_pro_in_active = true;
            break;
        }
    }
}
if (!$content_guardian_pro_in_active && function_exists('is_multisite') && is_multisite()) {
    $content_guardian_network_plugins = (array) get_site_option('active_sitewide_plugins', []);
    if (isset($content_guardian_network_plugins['content-guardian-pro/content-guardian-pro.php'])) {
        $content_guardian_pro_in_active = true;
    }
}
if (
    defined('CONTENT_GUARDIAN_PRO_VERSION')
    || $content_guardian_pro_in_active
    || get_option('content_guardian_pro_protection_level', false) !== false
) {
    return;
}

$content_guardian_free_options = [
    'content_guardian_disable_right_click',
    'content_guardian_disable_text_selection',
    'content_guardian_disable_copy_paste',
    'content_guardian_disable_drag_drop',
    'content_guardian_show_alerts',
    'content_guardian_custom_message',
    'content_guardian_admin_bypass',
    'content_guardian_delete_data_on_uninstall',
    'content_guardian_audit_notice_dismissed',
    'content_guardian_activated_at',
    'content_guardian_review_dismissed',
];

foreach ($content_guardian_free_options as $content_guardian_opt) {
    delete_option($content_guardian_opt);
}
delete_transient('cg_free_rest_probe_lock');
delete_transient('cg_free_ai_probe_lock');

global $wpdb;
$content_guardian_stats_table = $wpdb->prefix . 'content_guardian_statistics';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query($wpdb->prepare('DROP TABLE IF EXISTS %i', $content_guardian_stats_table));
