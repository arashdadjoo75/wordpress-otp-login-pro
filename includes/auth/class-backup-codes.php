<?php
/**
 * Backup Codes Manager
 * Generate and manage 2FA backup codes
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Backup_Codes {
    
    /**
     * Generate backup codes for user
     */
    public static function generate_for_user($user_id, $count = 10) {
        global $wpdb;
        
        // Delete old codes
        $wpdb->delete(
            "{$wpdb->prefix}otp_backup_codes",
            ['user_id' => $user_id],
            ['%d']
        );
        
        $codes = [];
        
        for ($i = 0; $i < $count; $i++) {
            $code = self::generate_code();
            $code_hash = wp_hash_password($code);
            
            $wpdb->insert(
                "{$wpdb->prefix}otp_backup_codes",
                [
                    'user_id' => $user_id,
                    'code_hash' => $code_hash,
                    'used' => 0,
                ],
                ['%d', '%s', '%d']
            );
            
            $codes[] = $code;
        }
        
        update_user_meta($user_id, '_otp_backup_codes_generated', time());
        
        return $codes;
    }
    
    /**
     * Generate single backup code
     */
    private static function generate_code() {
        // Format: XXXX-XXXX-XXXX
        $parts = [];
        
        for ($i = 0; $i < 3; $i++) {
            $parts[] = strtoupper(substr(md5(wp_rand()), 0, 4));
        }
        
        return implode('-', $parts);
    }
    
    /**
     * Verify backup code
     */
    public static function verify_code($user_id, $code) {
        global $wpdb;
        
        $codes = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}otp_backup_codes 
            WHERE user_id = %d AND used = 0
        ", $user_id));
        
        foreach ($codes as $stored_code) {
            if (wp_check_password($code, $stored_code->code_hash)) {
                // Mark as used
                $wpdb->update(
                    "{$wpdb->prefix}otp_backup_codes",
                    [
                        'used' => 1,
                        'used_at' => current_time('mysql'),
                        'used_ip' => self::get_ip(),
                    ],
                    ['id' => $stored_code->id],
                    ['%d', '%s', '%s'],
                    ['%d']
                );
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get remaining backup codes count
     */
    public static function get_remaining_count($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}otp_backup_codes 
            WHERE user_id = %d AND used = 0
        ", $user_id));
    }
    
    /**
     * Get all codes for user (for display)
     */
    public static function get_codes_for_user($user_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT id, used, used_at, created_at 
            FROM {$wpdb->prefix}otp_backup_codes 
            WHERE user_id = %d 
            ORDER BY created_at DESC
        ", $user_id));
    }
    
    /**
     * Helper to get IP
     */
    private static function get_ip() {
        return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
    }
}
