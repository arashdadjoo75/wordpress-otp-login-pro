<?php
/**
 * Uninstall Plugin
 * Cleanup on uninstall
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Only proceed if user wants to delete all data
if (get_option('otp_login_pro_delete_on_uninstall', false)) {
    
    global $wpdb;
    
    // Drop custom tables
    $tables = [
        "{$wpdb->prefix}otp_logs",
        "{$wpdb->prefix}otp_rate_limits",
        "{$wpdb->prefix}otp_trusted_devices",
        "{$wpdb->prefix}otp_backup_codes",
        "{$wpdb->prefix}otp_analytics",
        "{$wpdb->prefix}otp_phone_numbers",
        "{$wpdb->prefix}otp_settings",
        "{$wpdb->prefix}otp_credits",
        "{$wpdb->prefix}otp_transactions",
        "{$wpdb->prefix}otp_sessions",
    ];
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
    
    // Delete all options
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'otp_login_pro_%'");
    
    // Delete user meta
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_otp_%'");
    
    // Clear scheduled hooks
    wp_clear_scheduled_hook('otp_login_pro_cleanup_expired');
    wp_clear_scheduled_hook('otp_login_pro_generate_analytics');
    wp_clear_scheduled_hook('otp_login_pro_cleanup_old_logs');
}
