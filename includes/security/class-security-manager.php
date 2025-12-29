<?php
/**
 * Security Manager
 * Handles brute force protection, device fingerprinting, CAPTCHA, etc.
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Security_Manager {
    
    public function __construct() {
        add_action('otp_login_pro_verification_failed', [$this, 'log_failed_attempt'], 10, 2);
        add_filter('otp_login_pro_require_captcha', [$this, 'check_captcha_requirement'], 10, 2);
    }
    
    public function log_failed_attempt($identifier, $otp) {
        // Log for security monitoring
        do_action('otp_login_pro_security_event', 'failed_verify', $identifier);
    }
    
    public function check_captcha_requirement($required, $identifier) {
        global $wpdb;
        
        if (!get_option('otp_login_pro_captcha_enabled', false)) {
            return false;
        }
        
        // Check failed attempts
        $failed_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}otp_logs 
            WHERE identifier = %s 
            AND status = 'failed'
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ", $identifier));
        
        return $failed_count >= 3;
    }
    
    public function verify_captcha($response, $provider = 'recaptcha') {
        if ($provider === 'recaptcha') {
            return $this->verify_recaptcha($response);
        }
        return false;
    }
    
    private function verify_recaptcha($response) {
        $secret = get_option('otp_login_pro_captcha_secret_key');
        
        if (empty($secret) || empty($response)) {
            return false;
        }
        
        $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
        
        $result = wp_remote_post($verify_url, [
            'body' => [
                'secret' => $secret,
                'response' => $response,
            ],
        ]);
        
        if (is_wp_error($result)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($result), true);
        
        return isset($body['success']) && $body['success'] === true;
    }
}
