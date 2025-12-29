<?php
/**
 * WooCommerce Integration
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Integration_WooCommerce {
    
    public function __construct() {
        if (!get_option('otp_login_pro_woocommerce_enabled', false)) {
            return;
        }
        
        // Add OTP to checkout
        if (get_option('otp_login_pro_woocommerce_checkout', false)) {
            add_action('woocommerce_after_checkout_billing_form', [$this, 'add_otp_to_checkout']);
            add_action('woocommerce_checkout_process', [$this, 'verify_checkout_otp']);
        }
        
        // Add OTP to my account
        add_action('woocommerce_before_customer_login_form', [$this, 'add_otp_login']);
    }
    
    public function add_otp_to_checkout() {
        echo '<div class="otp-checkout-verification">';
        echo '<h3>' . __('Phone Verification', 'otp-login-pro') . '</h3>';
        $shortcodes = new OTP_Login_Pro_Shortcodes();
        echo $shortcodes->phone_verify([]);
        echo '</div>';
    }
    
    public function verify_checkout_otp() {
        // Verification logic
    }
    
    public function add_otp_login() {
        $shortcodes = new OTP_Login_Pro_Shortcodes();
        echo $shortcodes->login_form(['title' => __('Login to Your Account', 'otp-login-pro')]);
    }
}
