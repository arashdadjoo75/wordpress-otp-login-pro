<?php
/**
 * Installer Class
 * Handles plugin activation, database creation, and migrations
 */

if (!defined('ABSPATH')) {
    exit;
}

class OTP_Login_Pro_Installer {
    
    /**
     * Activate plugin
     */
    public function activate() {
        $this->create_tables();
        $this->set_default_options();
        $this->create_capabilities();
        $this->schedule_events();
        
        // Set activation flag
        update_option('otp_login_pro_activated', time());
        update_option('otp_login_pro_version', OTP_LOGIN_PRO_VERSION);
        
        do_action('otp_login_pro_activated');
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $tables = [];
        
        // OTP Logs table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}otp_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NULL,
            identifier VARCHAR(255) NOT NULL,
            otp_hash VARCHAR(255) NOT NULL,
            method ENUM('sms', 'email', 'whatsapp', 'voice', 'telegram', 'push') NOT NULL DEFAULT 'sms',
            provider VARCHAR(50) NOT NULL,
            status ENUM('sent', 'verified', 'expired', 'failed', 'pending') NOT NULL DEFAULT 'pending',
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT NULL,
            device_fingerprint VARCHAR(255) NULL,
            attempts INT UNSIGNED DEFAULT 0,
            cost DECIMAL(10,4) DEFAULT 0.0000,
            metadata JSON NULL,
            sent_at DATETIME NULL,
            verified_at DATETIME NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY identifier (identifier),
            KEY status (status),
            KEY created_at (created_at),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        // Rate Limits table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}otp_rate_limits (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            identifier VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            attempt_count INT UNSIGNED DEFAULT 0,
            last_attempt DATETIME NOT NULL,
            blocked_until DATETIME NULL,
            reason VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_limit (identifier, ip_address),
            KEY identifier (identifier),
            KEY ip_address (ip_address),
            KEY blocked_until (blocked_until)
        ) $charset_collate;";
        
        // Trusted Devices table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}otp_trusted_devices (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            device_token VARCHAR(255) NOT NULL,
            device_name VARCHAR(255) NULL,
            device_fingerprint VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT NULL,
            location VARCHAR(255) NULL,
            last_used DATETIME NOT NULL,
            trusted_at DATETIME NOT NULL,
            expires_at DATETIME NOT NULL,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_device (device_token),
            KEY user_id (user_id),
            KEY device_fingerprint (device_fingerprint),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        // Backup Codes table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}otp_backup_codes (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            code_hash VARCHAR(255) NOT NULL,
            used BOOLEAN DEFAULT 0,
            used_at DATETIME NULL,
            used_ip VARCHAR(45) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY used (used)
        ) $charset_collate;";
        
        // Analytics table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}otp_analytics (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            date DATE NOT NULL,
            metric_type VARCHAR(50) NOT NULL,
            metric_value INT UNSIGNED DEFAULT 0,
            metadata JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_metric (date, metric_type),
            KEY date (date),
            KEY metric_type (metric_type)
        ) $charset_collate;";
        
        // Phone Numbers table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}otp_phone_numbers (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            phone_number VARCHAR(20) NOT NULL,
            country_code VARCHAR(5) NOT NULL,
            is_primary BOOLEAN DEFAULT 0,
            is_verified BOOLEAN DEFAULT 0,
            verified_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_phone (phone_number),
            KEY user_id (user_id),
            KEY is_verified (is_verified)
        ) $charset_collate;";
        
        // Settings table (for complex settings)
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}otp_settings (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            setting_group VARCHAR(100) NOT NULL,
            setting_key VARCHAR(100) NOT NULL,
            setting_value LONGTEXT NULL,
            autoload BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_setting (setting_group, setting_key),
            KEY autoload (autoload)
        ) $charset_collate;";
        
        // Credits table (for monetization)
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}otp_credits (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            credits INT DEFAULT 0,
            total_spent DECIMAL(10,2) DEFAULT 0.00,
            last_recharge DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user (user_id)
        ) $charset_collate;";
        
        // Transactions table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}otp_transactions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            type ENUM('purchase', 'usage', 'refund', 'bonus') NOT NULL,
            amount INT NOT NULL,
            balance_after INT NOT NULL,
            description TEXT NULL,
            reference VARCHAR(100) NULL,
            metadata JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Sessions table (for advanced session management)
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}otp_sessions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_key VARCHAR(255) NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            identifier VARCHAR(255) NULL,
            otp_id BIGINT UNSIGNED NULL,
            step VARCHAR(50) DEFAULT 'request',
            data JSON NULL,
            ip_address VARCHAR(45) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_session (session_key),
            KEY user_id (user_id),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        // Execute table creation
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        foreach ($tables as $table_sql) {
            dbDelta($table_sql);
        }
        
        // Add indexes for performance
        $this->add_indexes();
        
        do_action('otp_login_pro_tables_created');
    }
    
    /**
     * Add additional indexes for performance
     */
    private function add_indexes() {
        global $wpdb;
        
        // Add composite indexes if they don't exist
        $indexes = [
            "{$wpdb->prefix}otp_logs" => [
                "idx_user_status" => "(user_id, status)",
                "idx_created_status" => "(created_at, status)"
            ],
            "{$wpdb->prefix}otp_analytics" => [
                "idx_date_type" => "(date, metric_type)"
            ]
        ];
        
        foreach ($indexes as $table => $table_indexes) {
            foreach ($table_indexes as $index_name => $columns) {
                $wpdb->query("ALTER TABLE {$table} ADD INDEX {$index_name} {$columns}");
            }
        }
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $defaults = [
            // General Settings
            'otp_login_pro_enabled' => '1',
            'otp_login_pro_method' => 'both', // sms, email, both
            'otp_login_pro_otp_length' => '6',
            'otp_login_pro_otp_type' => 'numeric', // numeric, alphanumeric, word
            'otp_login_pro_expiry' => '300', // 5 minutes
            'otp_login_pro_cooldown' => '60', // 1 minute
            'otp_login_pro_max_attempts' => '3',
            
            // UI/UX
            'otp_login_pro_theme' => 'modern',
            'otp_login_pro_dark_mode' => '0',
            'otp_login_pro_auto_fill' => '1',
            'otp_login_pro_sound' => '0',
            'otp_login_pro_haptic' => '0',
            
            // Security
            'otp_login_pro_rate_limit_enabled' => '1',
            'otp_login_pro_rate_limit_requests' => '5',
            'otp_login_pro_rate_limit_window' => '300', // 5 minutes
            'otp_login_pro_brute_force_enabled' => '1',
            'otp_login_pro_device_fingerprint' => '1',
            'otp_login_pro_trusted_devices' => '1',
            'otp_login_pro_trusted_duration' => '30', // days
            'otp_login_pro_geo_blocking' => '0',
            'otp_login_pro_blocked_countries' => '',
            'otp_login_pro_captcha_enabled' => '0',
            'otp_login_pro_captcha_provider' => 'recaptcha',
            'otp_login_pro_captcha_site_key' => '',
            'otp_login_pro_captcha_secret_key' => '',
            
            // SMS Providers
            'otp_login_pro_sms_provider' => 'twilio',
            'otp_login_pro_sms_failover' => '1',
            'otp_login_pro_sms_providers_priority' => json_encode(['twilio', 'vonage']),
            
            // Email Settings
            'otp_login_pro_email_provider' => 'wp_mail',
            'otp_login_pro_email_from_name' => get_bloginfo('name'),
            'otp_login_pro_email_from_email' => get_bloginfo('admin_email'),
            'otp_login_pro_email_subject' => __('Your Login Code', 'otp-login-pro'),
            'otp_login_pro_email_template' => 'default',
            
            // SMS Template
            'otp_login_pro_sms_template' => __('Your login code is: {otp}. Valid for {expiry} minutes.', 'otp-login-pro'),
            
            // Registration
            'otp_login_pro_registration_enabled' => '1',
            'otp_login_pro_registration_role' => 'subscriber',
            'otp_login_pro_registration_auto_login' => '1',
            'otp_login_pro_registration_fields' => json_encode(['name', 'email', 'phone']),
            
            // 2FA Settings
            'otp_login_pro_2fa_enabled' => '0',
            'otp_login_pro_2fa_mandatory_roles' => json_encode(['administrator']),
            'otp_login_pro_backup_codes_count' => '10',
            
            // Analytics
            'otp_login_pro_analytics_enabled' => '1',
            'otp_login_pro_log_retention' => '90', // days
            
            // Monetization
            'otp_login_pro_credits_enabled' => '0',
            'otp_login_pro_cost_per_sms' => '0.05',
            'otp_login_pro_cost_per_email' => '0.01',
            'otp_login_pro_free_credits' => '100',
            
            // Integrations
            'otp_login_pro_woocommerce_enabled' => '0',
            'otp_login_pro_woocommerce_checkout' => '0',
            'otp_login_pro_woocommerce_high_value' => '100',
            
            // Advanced
            'otp_login_pro_async_sending' => '1',
            'otp_login_pro_queue_enabled' => '1',
            'otp_login_pro_webhook_url' => '',
            'otp_login_pro_api_enabled' => '1',
            'otp_login_pro_white_label' => '0',
            
            // Multisite
            'otp_login_pro_network_mode' => '0',
        ];
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
        
        do_action('otp_login_pro_default_options_set');
    }
    
    /**
     * Create custom capabilities
     */
    private function create_capabilities() {
        $admin_role = get_role('administrator');
        
        $capabilities = [
            'manage_otp_settings',
            'view_otp_analytics',
            'manage_otp_users',
            'view_otp_logs',
            'export_otp_data',
        ];
        
        if ($admin_role) {
            foreach ($capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }
    }
    
    /**
     * Schedule cron events
     */
    private function schedule_events() {
        // Clean up expired OTPs hourly
        if (!wp_next_scheduled('otp_login_pro_cleanup_expired')) {
            wp_schedule_event(time(), 'hourly', 'otp_login_pro_cleanup_expired');
        }
        
        // Generate daily analytics
        if (!wp_next_scheduled('otp_login_pro_generate_analytics')) {
            wp_schedule_event(strtotime('tomorrow midnight'), 'daily', 'otp_login_pro_generate_analytics');
        }
        
        // Clean old logs weekly
        if (!wp_next_scheduled('otp_login_pro_cleanup_old_logs')) {
            wp_schedule_event(time(), 'weekly', 'otp_login_pro_cleanup_old_logs');
        }
    }
    
    /**
     * Deactivation cleanup
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('otp_login_pro_cleanup_expired');
        wp_clear_scheduled_hook('otp_login_pro_generate_analytics');
        wp_clear_scheduled_hook('otp_login_pro_cleanup_old_logs');
        
        do_action('otp_login_pro_deactivated');
    }
    
    /**
     * Uninstall cleanup (called from uninstall.php)
     */
    public static function uninstall() {
        global $wpdb;
        
        // Delete tables if configured to do so
        if (get_option('otp_login_pro_delete_data_on_uninstall', false)) {
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
            
            // Delete all plugin options
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'otp_login_pro_%'");
            
            // Delete user meta
            $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_otp_%'");
        }
        
        do_action('otp_login_pro_uninstalled');
    }
}
