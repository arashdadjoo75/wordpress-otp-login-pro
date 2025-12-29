<?php
/**
 * Credits & Monetization Manager
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Credits_Manager {
    
    /**
     * Get user credits
     */
    public static function get_balance($user_id) {
        global $wpdb;
        
        $record = $wpdb->get_row($wpdb->prepare("
            SELECT credits FROM {$wpdb->prefix}otp_credits 
            WHERE user_id = %d
        ", $user_id));
        
        return $record ? intval($record->credits) : 0;
    }
    
    /**
     * Add credits
     */
    public static function add_credits($user_id, $amount, $description = '') {
        global $wpdb;
        
        // Update or create credit record
        $current = self::get_balance($user_id);
        $new_balance = $current + $amount;
        
        $wpdb->query($wpdb->prepare("
            INSERT INTO {$wpdb->prefix}otp_credits (user_id, credits)
            VALUES (%d, %d)
            ON DUPLICATE KEY UPDATE credits = %d
        ", $user_id, $new_balance, $new_balance));
        
        // Log transaction
        self::log_transaction($user_id, 'purchase', $amount, $new_balance, $description);
        
        do_action('otp_login_pro_credits_added', $user_id, $amount, $new_balance);
        
        return $new_balance;
    }
    
    /**
     * Deduct credits
     */
    public static function deduct_credits($user_id, $amount, $description = '') {
        $current = self::get_balance($user_id);
        
        if ($current < $amount) {
            return false; // Insufficient balance
        }
        
        return self::add_credits($user_id, -$amount, $description);
    }
    
    /**
     * Check if user can send OTP
     */
    public static function can_send_otp($user_id, $method = 'sms') {
        if (!get_option('otp_login_pro_credits_enabled', false)) {
            return true; // Credits disabled
        }
        
        $cost = $method === 'sms' ? 
            floatval(get_option('otp_login_pro_cost_per_sms', 0.05)) : 
            floatval(get_option('otp_login_pro_cost_per_email', 0.01));
        
        $credits_needed = ceil($cost * 100); // Convert to credits
        
        return self::get_balance($user_id) >= $credits_needed;
    }
    
    /**
     * Charge for OTP
     */
    public static function charge_for_otp($user_id, $method, $provider_cost = 0) {
        if (!get_option('otp_login_pro_credits_enabled', false)) {
            return true;
        }
        
        $cost = $method === 'sms' ? 
            floatval(get_option('otp_login_pro_cost_per_sms', 0.05)) : 
            floatval(get_option('otp_login_pro_cost_per_email', 0.01));
        
        $credits = ceil($cost * 100);
        
        return self::deduct_credits($user_id, $credits, "OTP via {$method}");
    }
    
    /**
     * Log transaction
     */
    private static function log_transaction($user_id, $type, $amount, $balance_after, $description) {
        global $wpdb;
        
        $wpdb->insert(
            "{$wpdb->prefix}otp_transactions",
            [
                'user_id' => $user_id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $balance_after,
                'description' => $description,
            ],
            ['%d', '%s', '%d', '%d', '%s']
        );
    }
    
    /**
     * Get transaction history
     */
    public static function get_transactions($user_id, $limit = 50) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}otp_transactions 
            WHERE user_id = %d 
            ORDER BY created_at DESC 
            LIMIT %d
        ", $user_id, $limit));
    }
    
    /**
     * Process credit purchase
     */
    public static function process_purchase($user_id, $package_id) {
        $packages = [
            'small' => ['credits' => 100, 'price' => 5.00],
            'medium' => ['credits' => 500, 'price' => 20.00],
            'large' => ['credits' => 1000, 'price' => 35.00],
        ];
        
        if (!isset($packages[$package_id])) {
            return false;
        }
        
        $package = $packages[$package_id];
        
        // Here you would integrate with payment gateway
        // For now, just add credits
        
        $new_balance = self::add_credits(
            $user_id, 
            $package['credits'], 
            "Purchased {$package_id} package"
        );
        
        return $new_balance;
    }
}
