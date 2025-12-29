<?php
/**
 * Integration Manager - WooCommerce, Elementor, etc.
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Integration_Manager {
    
    public function __construct() {
        // WooCommerce
        if (class_exists('WooCommerce')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'integrations/class-integration-woocommerce.php';
            new OTP_Login_Pro_Integration_WooCommerce();
        }
        
        // Elementor
        if (did_action('elementor/loaded')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'integrations/class-integration-elementor.php';
            new OTP_Login_Pro_Integration_Elementor();
        }
        
        // BuddyPress
        if (class_exists('BuddyPress')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'integrations/class-integration-buddypress.php';
            new OTP_Login_Pro_Integration_BuddyPress();
        }
    }
}
