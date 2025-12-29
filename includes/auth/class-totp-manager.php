<?php
/**
 * TOTP (Time-based OTP) Manager
 * Google Authenticator compatible
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_TOTP_Manager {
    
    /**
     * Generate TOTP secret
     */
    public static function generate_secret($length = 16) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32
        $secret = '';
        
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[wp_rand(0, strlen($chars) - 1)];
        }
        
        return $secret;
    }
    
    /**
     * Get current TOTP code
     */
    public static function get_code($secret, $time = null) {
        if ($time === null) {
            $time = time();
        }
        
        $time = floor($time / 30); // 30-second window
        
        $secret_decoded = self::base32_decode($secret);
        $hash = hash_hmac('sha1', pack('N*', 0, $time), $secret_decoded, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Verify TOTP code
     */
    public static function verify_code($secret, $code, $discrepancy = 1) {
        $time = time();
        
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $test_time = $time + ($i * 30);
            if (self::get_code($secret, $test_time) === $code) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get QR code URL for Google Authenticator
     */
    public static function get_qr_code_url($secret, $user_email, $issuer = null) {
        if ($issuer === null) {
            $issuer = get_bloginfo('name');
        }
        
        $issuer = rawurlencode($issuer);
        $user_email = rawurlencode($user_email);
        
        $otpauth = "otpauth://totp/{$issuer}:{$user_email}?secret={$secret}&issuer={$issuer}";
        
        return 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($otpauth);
    }
    
    /**
     * Base32 decode
     */
    private static function base32_decode($secret) {
        if (empty($secret)) return '';
        
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));
        
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = [6, 4, 3, 1, 0];
        
        if (!in_array($paddingCharCount, $allowedValues)) return false;
        
        for ($i = 0; $i < 4; $i++) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) != str_repeat('=', $allowedValues[$i])) {
                return false;
            }
        }
        
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';
        
        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = '';
            if (!in_array($secret[$i], $base32charsFlipped)) return false;
            
            for ($j = 0; $j < 8; $j++) {
                $x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
            
            $eightBits = str_split($x, 8);
            
            for ($z = 0; $z < count($eightBits); $z++) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
            }
        }
        
        return $binaryString;
    }
    
    /**
     * Enable TOTP for user
     */
    public static function enable_for_user($user_id) {
        $secret = self::generate_secret();
        update_user_meta($user_id, '_otp_totp_secret', $secret);
        update_user_meta($user_id, '_otp_totp_enabled', true);
        
        return $secret;
    }
    
    /**
     * Disable TOTP for user
     */
    public static function disable_for_user($user_id) {
        delete_user_meta($user_id, '_otp_totp_secret');
        delete_user_meta($user_id, '_otp_totp_enabled');
    }
    
    /**
     * Check if user has TOTP enabled
     */
    public static function is_enabled_for_user($user_id) {
        return (bool) get_user_meta($user_id, '_otp_totp_enabled', true);
    }
}
