<?php
/**
 * MemberPress Integration
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Integration_MemberPress {
    
    public function __construct() {
        if (!class_exists('MeprUser')) return;
        
        add_action('mepr-account-nav', [$this, 'add_2fa_tab']);
        add_action('mepr-account-nav-content', [$this, 'show_2fa_content']);
        add_filter('mepr-validate-login', [$this, 'validate_2fa'], 10, 2);
    }
    
    public function add_2fa_tab($user) {
        ?>  
        <li class="mepr-account-nav-item">
            <a href="#" data-id="otp-2fa"><?php _e('Two-Factor Auth', 'otp-login-pro'); ?></a>
        </li>
        <?php
    }
    
    public function show_2fa_content($user) {
        ?>
        <div id="otp-2fa" class="mepr-account-content">
            <h3><?php _e('Two-Factor Authentication', 'otp-login-pro'); ?></h3>
            
            <?php if (OTP_Login_Pro_TOTP_Manager::is_enabled_for_user($user->ID)): ?>
                <p class="otp-enabled"><?php _e('2FA is currently enabled', 'otp-login-pro'); ?></p>
                <button class="mepr-btn" id="disable-2fa"><?php _e('Disable 2FA', 'otp-login-pro'); ?></button>
            <?php else: ?>
                <p><?php _e('Enhance your account security with 2FA', 'otp-login-pro'); ?></p>
                <button class="mepr-btn" id="enable-2fa"><?php _e('Enable 2FA', 'otp-login-pro'); ?></button>
            <?php endif; ?>
            
            <div id="2fa-setup" style="display:none;">
                <!-- QR code will be displayed here -->
            </div>
        </div>
        <?php
    }
    
    public function validate_2fa($errors, $user) {
        if (OTP_Login_Pro_TOTP_Manager::is_enabled_for_user($user->ID)) {
            $totp_code = isset($_POST['totp_code']) ? sanitize_text_field($_POST['totp_code']) : '';
            
            if (empty($totp_code)) {
                $errors[] = __('Please enter your 2FA code', 'otp-login-pro');
            } else {
                $secret = get_user_meta($user->ID, '_otp_totp_secret', true);
                
                if (!OTP_Login_Pro_TOTP_Manager::verify_code($secret, $totp_code)) {
                    $errors[] = __('Invalid 2FA code', 'otp-login-pro');
                }
            }
        }
        
        return $errors;
    }
}

new OTP_Login_Pro_Integration_MemberPress();
