<?php
/**
 * OTP Validator
 * Validates OTPs and manages attempts
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_OTP_Validator {
    
    /**
     * Validate OTP
     */
    public static function validate($identifier, $otp_code) {
        global $wpdb;
        
        // Get the latest OTP for this identifier
        $otp_record = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}otp_logs 
            WHERE identifier = %s 
            AND status = 'sent'
            AND expires_at > NOW()
            ORDER BY created_at DESC 
            LIMIT 1
        ", $identifier));
        
        if (!$otp_record) {
            return [
                'valid' => false,
                'message' => __('No valid OTP found or OTP expired', 'otp-login-pro'),
                'code' => 'no_otp',
            ];
        }
        
        // Check max attempts
        $max_attempts = intval(get_option('otp_login_pro_max_attempts', 3));
        if ($otp_record->attempts >= $max_attempts) {
            // Mark as failed
            $wpdb->update(
                "{$wpdb->prefix}otp_logs",
                ['status' => 'failed'],
                ['id' => $otp_record->id]
            );
            
            return [
                'valid' => false,
                'message' => __('Maximum attempts exceeded', 'otp-login-pro'),
                'code' => 'max_attempts',
            ];
        }
        
        // Increment attempt count
        $wpdb->query($wpdb->prepare("
            UPDATE {$wpdb->prefix}otp_logs 
            SET attempts = attempts + 1 
            WHERE id = %d
        ", $otp_record->id));
        
        // Verify OTP
        if (!OTP_Login_Pro_OTP_Generator::verify_hash($otp_code, $otp_record->otp_hash)) {
            return [
                'valid' => false,
                'message' => __('Invalid OTP code', 'otp-login-pro'),
                'code' => 'invalid_otp',
                'attempts_remaining' => $max_attempts - ($otp_record->attempts + 1),
            ];
        }
        
        // Mark as verified
        $wpdb->update(
            "{$wpdb->prefix}otp_logs",
            [
                'status' => 'verified',
                'verified_at' => current_time('mysql'),
            ],
            ['id' => $otp_record->id]
        );
        
        return [
            'valid' => true,
            'message' => __('OTP verified successfully', 'otp-login-pro'),
            'code' => 'success',
            'otp_id' => $otp_record->id,
            'user_id' => $otp_record->user_id,
        ];
    }
    
    /**
     * Check if OTP exists and is valid
     */
    public static function exists($identifier) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}otp_logs 
            WHERE identifier = %s 
            AND status = 'sent'
            AND expires_at > NOW()
        ", $identifier));
        
        return $count > 0;
    }
    
    /**
     * Invalidate all OTPs for identifier
     */
    public static function invalidate($identifier) {
        global $wpdb;
        
        $wpdb->update(
            "{$wpdb->prefix}otp_logs",
            ['status' => 'expired'],
            [
                'identifier' => $identifier,
                'status' => 'sent',
            ]
        );
    }
}
