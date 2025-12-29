<?php
/**
 * Session Manager
 * Handles OTP authentication sessions
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Session_Manager {
    
    /**
     * Create session
     */
    public static function create($identifier, $data = []) {
        global $wpdb;
        
        $session_key = self::generate_session_key();
        $expiry = intval(get_option('otp_login_pro_expiry', 300));
        
        $wpdb->insert(
            "{$wpdb->prefix}otp_sessions",
            [
                'session_key' => $session_key,
                'identifier' => $identifier,
                'data' => json_encode($data),
                'ip_address' => self::get_ip_address(),
                'expires_at' => date('Y-m-d H:i:s', time() + $expiry),
                'step' => 'request',
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        return $session_key;
    }
    
    /**
     * Get session
     */
    public static function get($session_key) {
        global $wpdb;
        
        $session = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}otp_sessions 
            WHERE session_key = %s 
            AND expires_at > NOW()
        ", $session_key));
        
        if ($session && $session->data) {
            $session->data = json_decode($session->data, true);
        }
        
        return $session;
    }
    
    /**
     * Update session
     */
    public static function update($session_key, $data = [], $step = null) {
        global $wpdb;
        
        $update_data = [];
        
        if (!empty($data)) {
            $update_data['data'] = json_encode($data);
        }
        
        if ($step) {
            $update_data['step'] = $step;
        }
        
        if (!empty($update_data)) {
            $wpdb->update(
                "{$wpdb->prefix}otp_sessions",
                $update_data,
                ['session_key' => $session_key],
                array_fill(0, count($update_data), '%s'),
                ['%s']
            );
        }
    }
    
    /**
     * Delete session
     */
    public static function delete($session_key) {
        global $wpdb;
        
        $wpdb->delete(
            "{$wpdb->prefix}otp_sessions",
            ['session_key' => $session_key],
            ['%s']
        );
    }
    
    /**
     * Generate session key
     */
    private static function generate_session_key() {
        return wp_generate_password(40, false);
    }
    
    /**
     * Get client IP address
     */
    private static function get_ip_address() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } else {
            return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
        }
    }
    
    /**
     * Clean expired sessions (called by cron)
     */
    public static function cleanup_expired() {
        global $wpdb;
        
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}otp_sessions 
            WHERE expires_at < NOW()
        ");
    }
}
