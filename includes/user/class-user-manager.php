<?php
if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_User_Manager extends OTP_Login_Pro_Registration {
    
    public function __construct() {
        add_action('show_user_profile', [$this, 'add_phone_fields']);
        add_action('edit_user_profile', [$this, 'add_phone_fields']);
        add_action('personal_options_update', [$this, 'save_phone_fields']);
        add_action('edit_user_profile_update', [$this, 'save_phone_fields']);
    }
    
    public function add_phone_fields($user) {
        $phone = get_user_meta($user->ID, '_otp_phone_number', true);
        $is_verified = get_user_meta($user->ID, '_otp_phone_verified', true);
        ?>
        <h3><?php _e('OTP Authentication', 'otp-login-pro'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="otp_phone"><?php _e('Phone Number', 'otp-login-pro'); ?></label></th>
                <td>
                    <input type="tel" name="otp_phone" id="otp_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" />
                    <?php if ($is_verified): ?>
                        <span class="description" style="color: green;">✓ <?php _e('Verified', 'otp-login-pro'); ?></span>
                    <?php else: ?>
                        <span class="description"><?php _e('Not verified', 'otp-login-pro'); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function save_phone_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        
        if (isset($_POST['otp_phone'])) {
            $phone = sanitize_text_field($_POST['otp_phone']);
            update_user_meta($user_id, '_otp_phone_number', $phone);
        }
    }
}
