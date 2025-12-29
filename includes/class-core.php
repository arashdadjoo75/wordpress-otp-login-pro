<?php
/**
 * Core Settings and Configuration Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class OTP_Login_Pro_Core {
    
    private $settings = [];
    
    public function __construct() {
        $this->load_settings();
        $this->init_hooks();
    }
    
    /**
     * Load all settings
     */
    private function load_settings() {
        // Load from WordPress options
        $this->settings = [
            'enabled' => $this->get_option('enabled', true),
            'method' => $this->get_option('method', 'both'),
            'otp_length' => $this->get_option('otp_length', 6),
            'otp_type' => $this->get_option('otp_type', 'numeric'),
            'expiry' => $this->get_option('expiry', 300),
            'cooldown' => $this->get_option('cooldown', 60),
            'max_attempts' => $this->get_option('max_attempts', 3),
        ];
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', [$this, 'register_post_types']);
        add_action('otp_login_pro_cleanup_expired', [$this, 'cleanup_expired_otps']);
        add_action('otp_login_pro_generate_analytics', [$this, 'generate_daily_analytics']);
        add_action('otp_login_pro_cleanup_old_logs', [$this, 'cleanup_old_logs']);
    }
    
    /**
     * Register custom post types (if needed for templates)
     */
    public function register_post_types() {
        // Register custom post type for email templates
        register_post_type('otp_email_template', [
            'labels' => [
                'name' => __('Email Templates', 'otp-login-pro'),
                'singular_name' => __('Email Template', 'otp-login-pro'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'supports' => ['title', 'editor'],
        ]);
    }
    
    /**
     * Get option with prefix
     */
    public function get_option($key, $default = '') {
        return get_option('otp_login_pro_' . $key, $default);
    }
    
    /**
     * Update option with prefix
     */
    public function update_option($key, $value) {
        return update_option('otp_login_pro_' . $key, $value);
    }
    
    /**
     * Get setting
     */
    public function get_setting($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
    
    /**
     * Cleanup expired OTPs (cron job)
     */
    public function cleanup_expired_otps() {
        global $wpdb;
        
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}otp_logs 
            WHERE expires_at < NOW() 
            AND status != 'verified'
        ");
        
        // Clean up expired sessions
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}otp_sessions 
            WHERE expires_at < NOW()
        ");
        
        // Clean up expired trusted devices
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}otp_trusted_devices 
            WHERE expires_at < NOW()
        ");
    }
    
    /**
     * Generate daily analytics (cron job)
     */
    public function generate_daily_analytics() {
        global $wpdb;
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Total OTP sent
        $total_sent = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}otp_logs 
            WHERE DATE(created_at) = %s
        ", $yesterday));
        
        $this->insert_analytics($yesterday, 'otp_sent', $total_sent);
        
        // Success rate
        $total_verified = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}otp_logs 
            WHERE DATE(verified_at) = %s AND status = 'verified'
        ", $yesterday));
        
        $this->insert_analytics($yesterday, 'otp_verified', $total_verified);
        
        // Failed attempts
        $total_failed = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}otp_logs 
            WHERE DATE(created_at) = %s AND status = 'failed'
        ", $yesterday));
        
        $this->insert_analytics($yesterday, 'otp_failed', $total_failed);
        
        // Total cost
        $total_cost = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(cost) FROM {$wpdb->prefix}otp_logs 
            WHERE DATE(created_at) = %s
        ", $yesterday));
        
        $this->insert_analytics($yesterday, 'total_cost', $total_cost * 10000); // Store as integer
    }
    
    /**
     * Insert analytics record
     */
    private function insert_analytics($date, $type, $value) {
        global $wpdb;
        
        $wpdb->replace(
            "{$wpdb->prefix}otp_analytics",
            [
                'date' => $date,
                'metric_type' => $type,
                'metric_value' => intval($value),
            ],
            ['%s', '%s', '%d']
        );
    }
    
    /**
     * Cleanup old logs (cron job)
     */
    public function cleanup_old_logs() {
        global $wpdb;
        
        $retention_days = intval($this->get_option('log_retention', 90));
        $cutoff_date = date('Y-m-d', strtotime("-{$retention_days} days"));
        
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->prefix}otp_logs 
            WHERE created_at < %s
        ", $cutoff_date));
    }
}
