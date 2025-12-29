<?php
/**
 * Rate Limiter
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Rate_Limiter {
    
    public static function check($identifier, $ip, $action = 'otp_request') {
        // Implementation in Auth Manager
        return ['allowed' => true];
    }
}
