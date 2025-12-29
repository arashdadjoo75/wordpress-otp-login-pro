<?php
// Kavenegar SMS Provider (Iranian)
if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Provider_SMS_Kavenegar extends OTP_Login_Pro_Abstract_Provider {
    
    protected function init() {
        $this->name = 'Kavenegar';
        $this->type = 'sms';
        $this->cost_per_message = 0.003;
    }
    
    public function send($recipient, $message, $options = []) {
        $api_key = $this->config['api_key'] ?? '';
        $sender = $this->config['sender'] ?? '';
        
        if (empty($api_key)) {
            return ['success' => false, 'message' => 'Kavenegar API key required'];
        }
        
        // Format for Iranian numbers
        $recipient = preg_replace('/[^0-9]/', '', $recipient);
        if (substr($recipient, 0, 1) === '0') {
            $recipient = '98' . substr($recipient, 1);
        }
        
        $url = "https://api.kavenegar.com/v1/{$api_key}/sms/send.json";
        
        $result = $this->make_request($url, [
            'method' => 'POST',
            'body' => [
                'receptor' => $recipient,
                'sender' => $sender,
                'message' => $message,
            ],
        ]);
        
        $this->log_attempt($result['success'], $recipient, $result);
        
        if ($result['success']) {
            $data = json_decode($result['body'], true);
            if (isset($data['return']['status']) && $data['return']['status'] == 200) {
                return ['success' => true, 'message' => 'SMS sent via Kavenegar', 'response' => $data];
            }
        }
        
        return ['success' => false, 'message' => 'Failed to send via Kavenegar'];
    }
    
    public function validate_config() {
        return [
            'valid' => !empty($this->config['api_key']),
            'errors' => empty($this->config['api_key']) ? ['API Key required'] : [],
        ];
    }
}
