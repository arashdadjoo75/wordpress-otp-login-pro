<?php
/**
 * Ghasedak SMS Provider (Iranian)
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Provider_SMS_Ghasedak extends OTP_Login_Pro_Abstract_Provider {
    
    protected function init() {
        $this->name = 'Ghasedak';
        $this->type = 'sms';
        $this->cost_per_message = 0.0025;
    }
    
    public function send($recipient, $message, $options = []) {
        $api_key = $this->config['api_key'] ?? '';
        $line_number = $this->config['line_number'] ?? '';
        
        if (empty($api_key)) {
            return ['success' => false, 'message' => 'Ghasedak API key required'];
        }
        
        // Format for Iranian numbers
        $recipient = preg_replace('/[^0-9]/', '', $recipient);
        if (substr($recipient, 0, 1) === '0') {
            $recipient = '98' . substr($recipient, 1);
        }
        
        $result = $this->make_request('https://api.ghasedak.me/v2/sms/send/simple', [
            'method' => 'POST',
            'headers' => [
                'apikey' => $api_key,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'message' => $message,
                'receptor' => $recipient,
                'linenumber' => $line_number,
            ],
        ]);
        
        $this->log_attempt($result['success'], $recipient, $result);
        
        if ($result['success']) {
            $data = json_decode($result['body'], true);
            if (isset($data['result']['code']) && $data['result']['code'] == 200) {
                return ['success' => true, 'message' => 'SMS sent via Ghasedak', 'response' => $data];
            }
        }
        
        return ['success' => false, 'message' => 'Failed to send via Ghasedak'];
    }
    
    public function validate_config() {
        return [
            'valid' => !empty($this->config['api_key']),
            'errors' => empty($this->config['api_key']) ? ['API Key required'] : [],
        ];
    }
    
    public function get_balance() {
        $api_key = $this->config['api_key'] ?? '';
        
        if (empty($api_key)) return null;
        
        $result = $this->make_request('https://api.ghasedak.me/v2/account/info', [
            'headers' => ['apikey' => $api_key],
        ]);
        
        if ($result['success']) {
            $data = json_decode($result['body'], true);
            return $data['result']['balance'] ?? null;
        }
        
        return null;
    }
}
