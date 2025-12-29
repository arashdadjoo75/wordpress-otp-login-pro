<?php
/**
 * Device Trust Manager
 * Manages trusted devices for "Remember Me" feature
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Device_Manager {
    
    /**
     * Check if current device is trusted
     */
    public static function is_trusted($user_id) {
        $device_token = self::get_device_token();
        
        if (!$device_token) {
            return false;
        }
        
        global $wpdb;
        
        $device = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}otp_trusted_devices 
            WHERE user_id = %d 
            AND device_token = %s 
            AND expires_at > NOW()
            AND is_active = 1
        ", $user_id, $device_token));
        
        if ($device) {
            // Update last used
            $wpdb->update(
                "{$wpdb->prefix}otp_trusted_devices",
                ['last_used' => current_time('mysql')],
                ['id' => $device->id]
            );
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Trust current device
     */
    public static function trust_device($user_id, $duration_days = 30) {
        global $wpdb;
        
        $device_token = wp_generate_password(40, false);
        $fingerprint = self::get_fingerprint();
        
        $wpdb->insert(
            "{$wpdb->prefix}otp_trusted_devices",
            [
                'user_id' => $user_id,
                'device_token' => $device_token,
                'device_name' => self::get_device_name(),
                'device_fingerprint' => $fingerprint,
                'ip_address' => self::get_ip(),
                'user_agent' => self::get_user_agent(),
                'location' => self::get_location(),
                'last_used' => current_time('mysql'),
                'trusted_at' => current_time('mysql'),
                'expires_at' => date('Y-m-d H:i:s', time() + ($duration_days * DAY_IN_SECONDS)),
                'is_active' => 1,
            ]
        );
        
        // Set cookie
        setcookie(
            'otp_device_token', 
            $device_token, 
            time() + ($duration_days * DAY_IN_SECONDS), 
            '/',
            '',
            is_ssl(),
            true // HttpOnly
        );
        
        do_action('otp_login_pro_device_trusted', $user_id, $device_token);
        
        return $device_token;
    }
    
    /**
     * Revoke device trust
     */
    public static function revoke_device($device_id) {
        global $wpdb;
        
        $wpdb->update(
            "{$wpdb->prefix}otp_trusted_devices",
            ['is_active' => 0],
            ['id' => $device_id]
        );
    }
    
    /**
     * Get all trusted devices for user
     */
    public static function get_user_devices($user_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}otp_trusted_devices 
            WHERE user_id = %d 
            ORDER BY last_used DESC
        ", $user_id));
    }
    
    /**
     * Get device token from cookie
     */
    private static function get_device_token() {
        return isset($_COOKIE['otp_device_token']) ? 
            sanitize_text_field($_COOKIE['otp_device_token']) : null;
    }
    
    /**
     * Get device fingerprint
     */
    private static function get_fingerprint() {
        return md5(self::get_user_agent() . self::get_ip());
    }
    
    /**
     * Get device name
     */
    private static function get_device_name() {
        $ua = self::get_user_agent();
        
        // Simple device detection
        if (preg_match('/iPhone|iPad|iPod/', $ua)) {
            return 'iOS Device';
        } elseif (preg_match('/Android/', $ua)) {
            return 'Android Device';
        } elseif (preg_match('/Windows/', $ua)) {
            return 'Windows PC';
        } elseif (preg_match('/Mac/', $ua)) {
            return 'Mac';
        }
        
        return 'Unknown Device';
    }
    
    /**
     * Get IP address
     */
    private static function get_ip() {
        return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
    }
    
    /**
     * Get user agent
     */
    private static function get_user_agent() {
        return sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');
    }
    
    /**
     * Get location (basic, could be enhanced with GeoIP)
     */
    private static function get_location() {
        // Placeholder - integrate with GeoIP service
        return '';
    }
}
