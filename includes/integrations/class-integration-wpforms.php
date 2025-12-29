<?php
/**
 * WPForms Integration
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Integration_WPForms {
    
    public function __construct() {
        if (!function_exists('wpforms')) return;
        
        add_filter('wpforms_field_new_display', [$this, 'add_otp_field']);
        add_action('wpforms_process_complete', [$this, 'verify_otp'], 10, 4);
    }
    
    public function add_otp_field($fields) {
        $fields['otp_verification'] = [
            'name' => __('OTP Verification', 'otp-login-pro'),
            'type' => 'otp_verification',
            'icon' => 'fa-shield',
            'order' => 100,
        ];
        
        return $fields;
    }
    
    public function verify_otp($fields, $entry, $form_data, $entry_id) {
        foreach ($form_data['fields'] as $field) {
            if ($field['type'] === 'otp_verification') {
                $otp = !empty($_POST['wpforms']['fields'][$field['id']]['otp']) ? $_POST['wpforms']['fields'][$field['id']]['otp'] : '';
                $phone = !empty($_POST['wpforms']['fields'][$field['id']]['phone']) ? $_POST['wpforms']['fields'][$field['id']]['phone'] : '';
                
                if (empty($otp) || empty($phone)) {
                    wpforms()->process->errors[$form_data['id']][$field['id']] = __('OTP verification required', 'otp-login-pro');
                    return;
                }
                
                $result = OTP_Login_Pro_OTP_Validator::validate($phone, $otp);
                
                if (!$result['valid']) {
                    wpforms()->process->errors[$form_data['id']][$field['id']] = $result['message'];
                }
            }
        }
    }
}

new OTP_Login_Pro_Integration_WPForms();
