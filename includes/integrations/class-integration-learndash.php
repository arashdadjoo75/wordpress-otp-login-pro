<?php
/**
 * LearnDash Integration
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Integration_LearnDash {
    
    public function __construct() {
        if (!defined('LEARNDASH_VERSION')) return;
        
        add_action('learndash_settings_fields_profile', [$this, 'add_profile_fields']);
        add_filter('learndash_login_form', [$this, 'add_otp_to_login']);
        add_action('learndash_before_login', [$this, 'require_otp']);
    }
    
    public function add_profile_fields($fields) {
        $fields[] = [
            'id' => 'otp_phone',
            'name' => __('Phone Number for OTP', 'otp-login-pro'),
            'type' => 'text',
            'placeholder' => __('+1234567890', 'otp-login-pro'),
        ];
        
        return $fields;
    }
    
    public function add_otp_to_login($form_html) {
        if (!get_option('otp_login_pro_learndash_enabled', false)) {
            return $form_html;
        }
        
        $otp_form = do_shortcode('[otp_loginform theme="minimal"]');
        
        return $form_html . '<div class="learndash-otp-login">' . $otp_form . '</div>';
    }
    
    public function require_otp() {
        if (get_option('otp_login_pro_learndash_mandatory', false)) {
            // Check if user has verified phone
            $user_id = get_current_user_id();
            if ($user_id) {
                $phone_verified = get_user_meta($user_id, '_otp_phone_verified', true);
                
                if (!$phone_verified) {
                    wp_redirect(add_query_arg('otp_required', '1', learndash_get_login_url()));
                    exit;
                }
            }
        }
    }
}

new OTP_Login_Pro_Integration_LearnDash();
