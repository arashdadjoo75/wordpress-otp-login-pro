<?php
/**
 * Shortcodes - All shortcode implementations  
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Shortcodes {
    
    public function __construct() {
        add_shortcode('otp_login_form', [$this, 'login_form']);
        add_shortcode('otp_register_form', [$this, 'register_form']);
        add_shortcode('otp_profile_manager', [$this, 'profile_manager']);
        add_shortcode('otp_phone_verify', [$this, 'phone_verify']);
    }
    
    /**
     * Main login form shortcode
     * Usage: [otp_login_form theme="modern" redirect="/dashboard"]
     */
    public function login_form($atts) {
        $atts = shortcode_atts([
            'theme' => get_option('otp_login_pro_theme', 'modern'),
            'redirect' => '',
            'title' => __('Login with OTP', 'otp-login-pro'),
            'method' => 'both', // sms, email, both
        ], $atts);
        
        ob_start();
        ?>
        <div class="otp-login-form-container otp-theme-<?php echo esc_attr($atts['theme']); ?>" data-redirect="<?php echo esc_attr($atts['redirect']); ?>">
            <div class="otp-form-header">
                <h2><?php echo esc_html($atts['title']); ?></h2>
            </div>
            
            <form class="otp-login-form" id="otp-login-form">
                <?php wp_nonce_field('otp_login_pro_nonce', 'otp_nonce'); ?>
                
                <div class="otp-step otp-step-1 active">
                    <div class="otp-form-group">
                        <label for="otp-identifier">
                            <?php 
                            if ($atts['method'] === 'sms') {
                                _e('Phone Number', 'otp-login-pro');
                            } elseif ($atts['method'] === 'email') {
                                _e('Email Address', 'otp-login-pro');
                            } else {
                                _e('Phone Number or Email', 'otp-login-pro');
                            }
                            ?>
                        </label>
                        <input type="text" 
                               id="otp-identifier" 
                               name="identifier" 
                               class="otp-input" 
                               placeholder="<?php esc_attr_e('Enter your phone or email', 'otp-login-pro'); ?>" 
                               required />
                    </div>
                    
                    <div class="otp-form-actions">
                        <button type="button" class="otp-button otp-button-primary" id="otp-send-btn">
                            <?php _e('Send OTP Code', 'otp-login-pro'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="otp-step otp-step-2" style="display:none;">
                    <div class="otp-info-message">
                        <p><?php _e('We sent a verification code to', 'otp-login-pro'); ?> <strong class="otp-identifier-display"></strong></p>
                    </div>
                    
                    <div class="otp-form-group">
                        <label for="otp-code"><?php _e('Verification Code', 'otp-login-pro'); ?></label>
                        <div class="otp-code-inputs">
                            <input type="text" maxlength="1" class="otp-digit" data-index="0" />
                            <input type="text" maxlength="1" class="otp-digit" data-index="1" />
                            <input type="text" maxlength="1" class="otp-digit" data-index="2" />
                            <input type="text" maxlength="1" class="otp-digit" data-index="3" />
                            <input type="text" maxlength="1" class="otp-digit" data-index="4" />
                            <input type="text" maxlength="1" class="otp-digit" data-index="5" />
                        </div>
                        <input type="hidden" id="otp-code" name="otp" />
                    </div>
                    
                    <div class="otp-timer-container">
                        <span class="otp-timer" id="otp-timer"></span>
                    </div>
                    
                    <div class="otp-form-group">
                        <label>
                            <input type="checkbox" name="remember" id="otp-remember" />
                            <?php _e('Remember this device for 30 days', 'otp-login-pro'); ?>
                        </label>
                    </div>
                    
                    <div class="otp-form-actions">
                        <button type="button" class="otp-button otp-button-primary" id="otp-verify-btn">
                            <?php _e('Verify & Login', 'otp-login-pro'); ?>
                        </button>
                        <button type="button" class="otp-button otp-button-secondary" id="otp-resend-btn" disabled>
                            <?php _e('Resend Code', 'otp-login-pro'); ?>
                        </button>
                    </div>
                    
                    <div class="otp-back-link">
                        <a href="#" id="otp-back-btn"><?php _e('← Change phone/email', 'otp-login-pro'); ?></a>
                    </div>
                </div>
                
                <div class="otp-messages"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Registration form shortcode
     * Usage: [otp_register_form]
     */
    public function register_form($atts) {
        if (!get_option('otp_login_pro_registration_enabled', false)) {
            return '<p>' . __('Registration is currently disabled', 'otp-login-pro') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="otp-register-form-container">
            <h2><?php _e('Register with OTP', 'otp-login-pro'); ?></h2>
            <?php echo $this->login_form(['title' => __('Create Account', 'otp-login-pro')]); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Profile phone manager
     * Usage: [otp_profile_manager]
     */
    public function profile_manager($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please login to manage your phone numbers', 'otp-login-pro') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $phone = get_user_meta($user_id, '_otp_phone_number', true);
        $is_verified = get_user_meta($user_id, '_otp_phone_verified', true);
        
        ob_start();
        ?>
        <div class="otp-profile-manager">
            <h3><?php _e('Phone Number Management', 'otp-login-pro'); ?></h3>
            
            <div class="otp-current-phone">
                <label><?php _e('Current Phone Number:', 'otp-login-pro'); ?></label>
                <span><?php echo esc_html($phone ?: __('Not set', 'otp-login-pro')); ?></span>
                <?php if ($is_verified): ?>
                    <span class="otp-verified-badge">✓ <?php _e('Verified', 'otp-login-pro'); ?></span>
                <?php endif; ?>
            </div>
            
            <form class="otp-update-phone-form">
                <input type="tel" name="new_phone" placeholder="<?php esc_attr_e('Enter new phone number', 'otp-login-pro'); ?>" />
                <button type="submit" class="otp-button"><?php _e('Update & Verify', 'otp-login-pro'); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Phone verification widget
     * Usage: [otp_phone_verify]
     */
    public function phone_verify($atts) {
        if (!is_user_logged_in()) {
            return '';
        }
        
        $user_id = get_current_user_id();
        $is_verified = (get_user_meta($user_id, '_otp_phone_verified', true);
        
        if ($is_verified) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="otp-verify-notice">
            <p><?php _e('Please verify your phone number to complete your profile', 'otp-login-pro'); ?></p>
            <button class="otp-button" id="otp-verify-phone-btn"><?php _e('Verify Now', 'otp-login-pro'); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }
}
