<?php
/**
 * Contact Form 7 Integration
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Integration_CF7 {
    
    public function __construct() {
        if (!function_exists('wpcf7')) return;
        
        add_action('wpcf7_init', [$this, 'add_otp_tag']);
        add_filter('wpcf7_validate_otp', [$this, 'validate_otp'], 10, 2);
    }
    
    public function add_otp_tag() {
        wpcf7_add_form_tag('otp', [$this, 'otp_tag_handler'], true);
    }
    
    public function otp_tag_handler($tag) {
        $html = '<div class="cf7-otp-field">';
        $html .= '<input type="tel" name="otp-phone" placeholder="' . __('Phone Number', 'otp-login-pro') . '" class="wpcf7-form-control" />';
        $html .= '<button type="button" class="cf7-otp-send">' . __('Send OTP', 'otp-login-pro') . '</button>';
        $html .= '<input type="text" name="otp-code" placeholder="' . __('Enter OTP', 'otp-login-pro') . '" maxlength="6" class="wpcf7-form-control" />';
        $html .= '</div>';
        
        return $html;
    }
    
    public function validate_otp($result, $tag) {
        $phone = isset($_POST['otp-phone']) ? sanitize_text_field($_POST['otp-phone']) : '';
        $otp = isset($_POST['otp-code']) ? sanitize_text_field($_POST['otp-code']) : '';
        
        if (empty($otp) || empty($phone)) {
            $result->invalidate($tag, __('OTP verification required', 'otp-login-pro'));
            return $result;
        }
        
        $validation = OTP_Login_Pro_OTP_Validator::validate($phone, $otp);
        
        if (!$validation['valid']) {
            $result->invalidate($tag, $validation['message']);
        }
        
        return $result;
    }
}

new OTP_Login_Pro_Integration_CF7();
