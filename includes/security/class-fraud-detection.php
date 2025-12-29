<?php
/**
 * Fraud Detection System
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Fraud_Detection {
    
    /**
     * Analyze login attempt for fraud
     */
    public static function analyze_attempt($identifier, $user_id = null) {
        $risk_score = 0;
        $flags = [];
        
        // Check 1: Multiple failed attempts
        $failed_count = self::get_failed_attempts($identifier);
        if ($failed_count > 3) {
            $risk_score += 30;
            $flags[] = 'multiple_failures';
        }
        
        // Check 2: Unusual location
        if ($user_id && self::is_unusual_location($user_id)) {
            $risk_score += 25;
            $flags[] = 'unusual_location';
        }
        
        // Check 3: Rapid requests
        if (self::has_rapid_requests($identifier)) {
            $risk_score += 20;
            $flags[] = 'rapid_requests';
        }
        
        // Check 4: New device
        if ($user_id && !self::is_known_device($user_id)) {
            $risk_score += 15;
            $flags[] = 'new_device';
        }
        
        // Check 5: Suspicious IP
        if (self::is_suspicious_ip()) {
            $risk_score += 35;
            $flags[] = 'suspicious_ip';
        }
        
        // Check 6: VPN/Proxy detection
        if (self::is_vpn_or_proxy()) {
            $risk_score += 10;
            $flags[] = 'vpn_detected';
        }
        
        $result = [
            'risk_score' => $risk_score,
            'risk_level' => self::get_risk_level($risk_score),
            'flags' => $flags,
            'requires_additional_verification' => $risk_score > 50,
        ];
        
        do_action('otp_login_pro_fraud_check', $identifier, $result);
        
        return $result;
    }
    
    /**
     * Get failed attempts count
     */
    private static function get_failed_attempts($identifier) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}otp_logs 
            WHERE identifier = %s 
            AND status = 'failed'
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ", $identifier));
    }
    
    /**
     * Check for unusual location
     */
    private static function is_unusual_location($user_id) {
        // Get user's typical locations from history
        global $wpdb;
        
        $current_ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
        
        $known_ips = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT ip_address FROM {$wpdb->prefix}otp_logs 
            WHERE user_id = %d 
            AND status = 'verified'
            LIMIT 10
        ", $user_id));
        
        return !in_array($current_ip, $known_ips);
    }
    
    /**
     * Check for rapid requests
     */
    private static function has_rapid_requests($identifier) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}otp_logs 
            WHERE identifier = %s 
            AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ", $identifier));
        
        return $count > 5;
    }
    
    /**
     * Check if device is known
     */
    private static function is_known_device($user_id) {
        $fingerprint = md5(
            sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '') .
            sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '')
        );
        
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}otp_trusted_devices 
            WHERE user_id = %d 
            AND device_fingerprint = %s
        ", $user_id, $fingerprint));
        
        return $count > 0;
    }
    
    /**
     * Check if IP is suspicious
     */
    private static function is_suspicious_ip() {
        $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
        
        // Check against known bad IP list (simplified)
        $blacklist = get_option('otp_login_pro_ip_blacklist', []);
        
        return in_array($ip, $blacklist);
    }
    
    /**
     * Detect VPN/Proxy
     */
    private static function is_vpn_or_proxy() {
        // Check for proxy headers
        $proxy_headers = [
            'HTTP_VIA',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'HTTP_PROXY_CONNECTION'
        ];
        
        foreach ($proxy_headers as $header) {
            if (!empty($_SERVER[$header])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get risk level
     */
    private static function get_risk_level($score) {
        if ($score < 20) return 'low';
        if ($score < 50) return 'medium';
        if ($score < 80) return 'high';
        return 'critical';
    }
    
    /**
     * Log suspicious activity
     */
    public static function log_suspicious_activity($identifier, $reason) {
        global $wpdb;
        
        $wpdb->insert(
            "{$wpdb->prefix}otp_logs",
            [
                'identifier' => $identifier,
                'status' => 'suspicious',
                'ip_address' => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'metadata' => json_encode(['reason' => $reason]),
            ]
        );
        
        // Alert admin if enabled
        if (get_option('otp_login_pro_fraud_alerts', false)) {
            self::send_fraud_alert($identifier, $reason);
        }
    }
    
    /**
     * Send fraud alert
     */
    private static function send_fraud_alert($identifier, $reason) {
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('[%s] Suspicious Login Activity Detected', 'otp-login-pro'), get_bloginfo('name'));
        
        $message = sprintf(
            __("Suspicious login activity detected:\n\nIdentifier: %s\nReason: %s\nIP: %s\nTime: %s", 'otp-login-pro'),
            $identifier,
            $reason,
            sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
            current_time('mysql')
        );
        
        wp_mail($admin_email, $subject, $message);
    }
}
