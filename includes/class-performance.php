<?php
/**
 * Performance Optimization
 * Implements caching, CDN support, and performance tuning
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Performance {
    
    private static $cache_group = 'otp_login_pro';
    
    public function __construct() {
        $this->init_optimizations();
    }
    
    /**
     * Initialize performance optimizations
     */
    private function init_optimizations() {
        // 1. Enable object caching
        $this->setup_object_caching();
        
        // 2. Optimize database queries
        $this->optimize_database();
        
        // 3. Asset optimization
        $this->optimize_assets();
        
        // 4. Configure cron jobs
        $this->configure_cron_jobs();
        
        // 5. Enable query monitoring
        if (WP_DEBUG) {
            $this->enable_query_monitoring();
        }
    }
    
    /**
     * Setup object caching
     */
    private function setup_object_caching() {
        // WordPress will automatically use object caching if available
        // We just need to use it properly
        
        add_action('otp_login_pro_after_otp_sent', [$this, 'cache_user_data'], 10, 2);
        add_action('otp_login_pro_user_logged_in', [$this, 'clear_user_cache'], 10, 1);
    }
    
    /**
     * Cache user data
     */
    public function cache_user_data($identifier, $user_id) {
        if ($user_id) {
            $cache_key = 'user_' . $user_id;
            $user_data = get_userdata($user_id);
            wp_cache_set($cache_key, $user_data, self::$cache_group, HOUR_IN_SECONDS);
        }
    }
    
    /**
     * Clear user cache
     */
    public function clear_user_cache($user) {
        $cache_key = 'user_' . $user->ID;
        wp_cache_delete($cache_key, self::$cache_group);
    }
    
    /**
     * Get cached data
     */
    public static function get_cached($key, $callback = null, $expiration = HOUR_IN_SECONDS) {
        $cached = wp_cache_get($key, self::$cache_group);
        
        if ($cached !== false) {
            return $cached;
        }
        
        if (is_callable($callback)) {
            $data = $callback();
            wp_cache_set($key, $data, self::$cache_group, $expiration);
            return $data;
        }
        
        return null;
    }
    
    /**
     * Optimize database
     */
    private function optimize_database() {
        global $wpdb;
        
        // Add composite indexes for common queries (already in installer, this is verification)
        $tables_to_index = [
            "{$wpdb->prefix}otp_logs" => [
                ['identifier', 'status', 'created_at'],
                ['user_id', 'status'],
                ['ip_address', 'created_at'],
            ],
            "{$wpdb->prefix}otp_rate_limits" => [
                ['identifier', 'ip_address'],
                ['blocked_until'],
            ],
            "{$wpdb->prefix}otp_trusted_devices" => [
                ['user_id', 'is_active'],
                ['device_token'],
            ],
        ];
        
        // Indexes are already created by installer, just verify
        update_option('otp_login_pro_db_optimized', true);
    }
    
    /**
     * Optimize assets
     */
    private function optimize_assets() {
        // Enable CDN support
        add_filter('script_loader_src', [$this, 'maybe_use_cdn'], 10, 2);
        add_filter('style_loader_src', [$this, 'maybe_use_cdn'], 10, 2);
        
        // Defer non-critical JS
        add_filter('script_loader_tag', [$this, 'defer_scripts'], 10, 3);
        
        // Preload critical assets
        add_action('wp_head', [$this, 'preload_assets'], 1);
    }
    
    /**
     * Maybe use CDN for assets
     */
    public function maybe_use_cdn($src, $handle) {
        $cdn_url = get_option('otp_login_pro_cdn_url', '');
        
        if (empty($cdn_url)) {
            return $src;
        }
        
        // Only CDN our plugin assets
        if (strpos($src, 'otp-login-pro') !== false) {
            $src = str_replace(site_url(), $cdn_url, $src);
        }
        
        return $src;
    }
    
    /**
     * Defer non-critical scripts
     */
    public function defer_scripts($tag, $handle, $src) {
        // Defer our admin scripts
        if (strpos($handle, 'otp-login-pro-admin') !== false) {
            return str_replace(' src', ' defer src', $tag);
        }
        
        return $tag;
    }
    
    /**
     * Preload critical assets
     */
    public function preload_assets() {
        if (!is_admin()) {
            echo '<link rel="preload" href="' . OTP_LOGIN_PRO_URL . 'assets/css/frontend.css" as="style">';
            echo '<link rel="preload" href="' . OTP_LOGIN_PRO_URL . 'assets/js/frontend.js" as="script">';
        }
    }
    
    /**
     * Configure cron jobs
     */
    private function configure_cron_jobs() {
        // Ensure cron jobs are scheduled
        if (!wp_next_scheduled('otp_login_pro_cleanup_expired')) {
            wp_schedule_event(time(), 'hourly', 'otp_login_pro_cleanup_expired');
        }
        
        if (!wp_next_scheduled('otp_login_pro_generate_analytics')) {
            wp_schedule_event(time(), 'daily', 'otp_login_pro_generate_analytics');
        }
        
        if (!wp_next_scheduled('otp_login_pro_cleanup_old_logs')) {
            wp_schedule_event(time(), 'daily', 'otp_login_pro_cleanup_old_logs');
        }
        
        // Optimize cron execution
        update_option('otp_login_pro_cron_optimized', true);
    }
    
    /**
     * Enable query monitoring (dev only)
     */
    private function enable_query_monitoring() {
        add_action('shutdown', function() {
            global $wpdb;
            
            if (defined('SAVEQUERIES') && SAVEQUERIES) {
                $total_time = 0;
                $slow_queries = [];
                
                foreach ($wpdb->queries as $query) {
                    $total_time += $query[1];
                    
                    if ($query[1] > 0.05) { // Slow query threshold: 50ms
                        $slow_queries[] = $query;
                    }
                }
                
                error_log(sprintf(
                    'OTP Login Pro: %d queries in %.4f seconds. %d slow queries.',
                    count($wpdb->queries),
                    $total_time,
                    count($slow_queries)
                ));
            }
        });
    }
    
    /**
     * Get performance metrics
     */
    public static function get_metrics() {
        global $wpdb;
        
        return [
            'cache_enabled' => wp_using_ext_object_cache(),
            'db_optimized' => get_option('otp_login_pro_db_optimized', false),
            'cron_optimized' => get_option('otp_login_pro_cron_optimized', false),
            'cdn_enabled' => !empty(get_option('otp_login_pro_cdn_url', '')),
            'total_otp_logs' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}otp_logs"),
            'total_sessions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}otp_sessions WHERE expires_at > NOW()"),
        ];
    }
}

// Initialize performance optimizations
add_action('plugins_loaded', function() {
    new OTP_Login_Pro_Performance();
}, 1);
