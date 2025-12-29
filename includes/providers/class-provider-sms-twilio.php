<?php
/**
 * Twilio SMS Provider
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Provider_SMS_Twilio extends OTP_Login_Pro_Abstract_Provider {
    
    protected function init() {
        $this->name = 'Twilio';
        $this->type = 'sms';
        $this->cost_per_message = 0.0079; // Average cost
    }
    
    public function send($recipient, $message, $options = []) {
        $account_sid = $this->config['account_sid'] ?? '';
        $auth_token = $this->config['auth_token'] ?? '';
        $from_number = $this->config['from_number'] ?? '';
        
        if (empty($account_sid) || empty($auth_token) || empty($from_number)) {
            return ['success' => false, 'message' => 'Twilio configuration incomplete'];
        }
        
        $recipient = $this->format_phone($recipient);
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";
        
        $result = $this->make_request($url, [
            'method' => 'POST',
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("{$account_sid}:{$auth_token}"),
            ],
            'body' => [
                'To' => $recipient,
                'From' => $from_number,
                'Body' => $message,
            ],
        ]);
        
        $this->log_attempt($result['success'], $recipient, $result);
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'SMS sent via Twilio',
                'response' => json_decode($result['body'], true),
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to send SMS via Twilio',
            'error' => $result['body'] ?? 'Unknown error',
        ];
    }
    
    public function validate_config() {
        $errors = [];
        
        if (empty($this->config['account_sid'])) {
            $errors[] = 'Account SID is required';
        }
        if (empty($this->config['auth_token'])) {
            $errors[] = 'Auth Token is required';
        }
        if (empty($this->config['from_number'])) {
            $errors[] = 'From Number is required';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
    
    public function get_balance() {
        $account_sid = $this->config['account_sid'] ?? '';
        $auth_token = $this->config['auth_token'] ?? '';
        
        if (empty($account_sid) || empty($auth_token)) {
            return null;
        }
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Balance.json";
        
        $result = $this->make_request($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("{$account_sid}:{$auth_token}"),
            ],
        ]);
        
        if ($result['success']) {
            $data = json_decode($result['body'], true);
            return $data['balance'] ?? null;
        }
        
        return null;
    }
}
