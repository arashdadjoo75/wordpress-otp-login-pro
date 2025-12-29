<?php
/**
 * OTP Generator
 * Generates various types of OTPs
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_OTP_Generator {
    
    /**
     * Generate OTP
     */
    public static function generate($length = 6, $type = 'numeric') {
        switch ($type) {
            case 'alphanumeric':
                return self::generate_alphanumeric($length);
            case 'word':
                return self::generate_word_based();
            case 'numeric':
            default:
                return self::generate_numeric($length);
        }
    }
    
    /**
     * Generate numeric OTP
     */
    private static function generate_numeric($length) {
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= wp_rand(0, 9);
        }
        return $otp;
    }
    
    /**
     * Generate alphanumeric OTP
     */
    private static function generate_alphanumeric($length) {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Removed ambiguous chars
        $otp = '';
        $max = strlen($characters) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $otp .= $characters[wp_rand(0, $max)];
        }
        
        return $otp;
    }
    
    /**
     * Generate word-based OTP (memorable)
     */
    private static function generate_word_based() {
        $words = ['APPLE', 'BEACH', 'CLOUD', 'DREAM', 'EAGLE', 'FLAME', 'GRACE', 'HEART', 'JAZZ', 'KNIGHT', 'LIGHT', 'MAGIC', 'OCEAN', 'PEACE', 'QUEST', 'RIVER', 'SPARK', 'TIGER', 'UNITY', 'WAVE'];
        
        $word1 = $words[array_rand($words)];
        $word2 = $words[array_rand($words)];
        $number = wp_rand(10, 99);
        
        return $word1 . $number . $word2;
    }
    
    /**
     * Hash OTP for storage
     */
    public static function hash($otp) {
        return wp_hash_password($otp);
    }
    
    /**
     * Verify OTP hash
     */
    public static function verify_hash($otp, $hash) {
        return wp_check_password($otp, $hash);
    }
}
