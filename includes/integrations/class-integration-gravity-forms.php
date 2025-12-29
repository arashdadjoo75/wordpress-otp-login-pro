<?php
/**
 * Gravity Forms Integration
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Integration_Gravity_Forms {
    
    public function __construct() {
        if (!class_exists('GFForms')) return;
        
        add_filter('gform_field_types', [$this, 'add_otp_field']);
        add_action('gform_field_standard_settings', [$this, 'field_settings'], 10, 2);
        add_filter('gform_field_content', [$this, 'field_content'], 10, 5);
        add_filter('gform_validation', [$this, 'validate_otp']);
    }
    
    public function add_otp_field($fields) {
        $fields[] = [
            'name' => 'otp_verification',
            'label' => __('OTP Verification', 'otp-login-pro'),
        ];
        return $fields;
    }
    
    public function field_settings($position, $form_id) {
        if ($position == 25) {
            ?>
            <li class="otp_verification_setting field_setting">
                <label><?php _e('OTP Method', 'otp-login-pro'); ?></label>
                <select id="otp_method">
                    <option value="sms"><?php _e('SMS', 'otp-login-pro'); ?></option>
                    <option value="email"><?php _e('Email', 'otp-login-pro'); ?></option>
                </select>
            </li>
            <?php
        }
    }
    
    public function field_content($content, $field, $value, $lead_id, $form_id) {
        if ($field->type === 'otp_verification') {
            $phone_field = '<input type="tel" name="otp_phone" placeholder="' . __('Phone Number', 'otp-login-pro') . '" />';
            $otp_field = '<input type="text" name="otp_code" placeholder="' . __('OTP Code', 'otp-login-pro') . '" maxlength="6" />';
            $send_btn = '<button type="button" class="gf-otp-send">' . __('Send OTP', 'otp-login-pro') . '</button>';
            
            $content = '<div class="gf-otp-field">' . $phone_field . $send_btn . $otp_field . '</div>';
        }
        
        return $content;
    }
    
    public function validate_otp($validation_result) {
        $form = $validation_result['form'];
        
        foreach ($form['fields'] as &$field) {
            if ($field->type === 'otp_verification') {
                $otp_code = rgpost('otp_code');
                $phone = rgpost('otp_phone');
                
                if (empty($otp_code)) {
                    $validation_result['is_valid'] = false;
                    $field->failed_validation = true;
                    $field->validation_message = __('Please enter OTP code', 'otp-login-pro');
                } else {
                    // Verify OTP
                    $result = OTP_Login_Pro_OTP_Validator::validate($phone, $otp_code);
                    
                    if (!$result['valid']) {
                        $validation_result['is_valid'] = false;
                        $field->failed_validation = true;
                        $field->validation_message = $result['message'];
                    }
                }
            }
        }
        
        $validation_result['form'] = $form;
        return $validation_result;
    }
}

new OTP_Login_Pro_Integration_Gravity_Forms();
