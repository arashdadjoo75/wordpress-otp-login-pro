<?php
/**
 * Security Configuration
 * Enables all security features for production
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Security_Config {
    
    public function __construct() {
        $this->apply_security_measures();
    }
    
    /**
     * Apply all security measures
     */
    private function apply_security_measures() {
        // 1. Enable rate limiting
        $this->enable_rate_limiting();
        
        // 2. Enable fraud detection
        $this->enable_fraud_detection();
        
        // 3. Enable CAPTCHA
        $this->enable_captcha();
        
        // 4. Enable webhook signatures
        $this->enable_webhook_signatures();
        
        // 5. Configure database security
        $this->configure_database_security();
        
        // 6. Enable IP restriction
        $this->enable_ip_restrictions();
    }
    
    /**
     * Enable rate limiting
     */
    private function enable_rate_limiting() {
        update_option('otp_login_pro_rate_limit_enabled', true);
        update_option('otp_login_pro_rate_limit_requests', 5); // 5 requests
        update_option('otp_login_pro_rate_limit_window', 300); // per 5 minutes
        update_option('otp_login_pro_block_duration', 3600); // Block for 1 hour
    }
    
    /**
     * Enable fraud detection
     */
    private function enable_fraud_detection() {
        update_option('otp_login_pro_fraud_detection_enabled', true);
        update_option('otp_login_pro_fraud_risk_threshold', 50); // Medium risk threshold
        update_option('otp_login_pro_fraud_alerts', true); // Email alerts
        update_option('otp_login_pro_fraud_auto_block', true); // Auto-block high risk
    }
    
    /**
     * Enable CAPTCHA
     */
    private function enable_captcha() {
        update_option('otp_login_pro_captcha_enabled', true);
        update_option('otp_login_pro_captcha_provider', 'recaptcha_v3');
        update_option('otp_login_pro_captcha_threshold', 3); // After 3 failed attempts
        
        // These need to be configured by admin
        update_option('otp_login_pro_captcha_site_key', '');
        update_option('otp_login_pro_captcha_secret_key', '');
    }
    
    /**
     * Enable webhook signatures
     */
    private function enable_webhook_signatures() {
        // Generate secure webhook secret
        $webhook_secret = wp_generate_password(32, false);
        update_option('otp_login_pro_webhook_secret', $webhook_secret);
        update_option('otp_login_pro_webhook_enabled', true);
        update_option('otp_login_pro_webhook_verify_ssl', true);
    }
    
    /**
     * Configure database security
     */
    private function configure_database_security() {
        // Use custom table prefix for added security
        update_option('otp_login_pro_custom_table_prefix', 'otppro_');
        
        // Enable encryption for sensitive data
        update_option('otp_login_pro_encrypt_otp', true);
        update_option('otp_login_pro_encrypt_phone', true);
        
        // Auto-cleanup settings
        update_option('otp_login_pro_cleanup_expired', true);
        update_option('otp_login_pro_cleanup_interval', 'hourly');
        update_option('otp_login_pro_log_retention_days', 30);
    }
    
    /**
     * Enable IP restrictions
     */
    private function enable_ip_restrictions() {
        update_option('otp_login_pro_ip_whitelist_enabled', false); // Disabled by default
        update_option('otp_login_pro_ip_blacklist_enabled', true);
        update_option('otp_login_pro_ip_blacklist', []); // Empty array
        update_option('otp_login_pro_block_vpn', false); // Disabled by default (can block legit users)
    }
    
    /**
     * Get security status
     */
    public static function get_security_status() {
        return [
            'rate_limiting' => get_option('otp_login_pro_rate_limit_enabled', false),
            'fraud_detection' => get_option('otp_login_pro_fraud_detection_enabled', false),
            'captcha' => get_option('otp_login_pro_captcha_enabled', false),
            'webhook_signatures' => get_option('otp_login_pro_webhook_enabled', false),
            'database_encryption' => get_option('otp_login_pro_encrypt_otp', false),
            'ip_blacklist' => get_option('otp_login_pro_ip_blacklist_enabled', false),
        ];
    }
    
    /**
     * Security score (0-100)
     */
    public static function get_security_score() {
        $status = self::get_security_status();
        $score = 0;
        
        if ($status['rate_limiting']) $score += 20;
        if ($status['fraud_detection']) $score += 25;
        if ($status['captcha']) $score += 15;
        if ($status['webhook_signatures']) $score += 10;
        if ($status['database_encryption']) $score += 20;
        if ($status['ip_blacklist']) $score += 10;
        
        return $score;
    }
}

// Auto-apply security on activation
register_activation_hook(OTP_LOGIN_PRO_FILE, function() {
    new OTP_Login_Pro_Security_Config();
});
