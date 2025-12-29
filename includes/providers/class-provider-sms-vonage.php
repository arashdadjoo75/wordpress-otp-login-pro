<?php
// Vonage (previously Nexmo) SMS Provider
if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Provider_SMS_Vonage extends OTP_Login_Pro_Abstract_Provider {
    
    protected function init() {
        $this->name = 'Vonage';
        $this->type = 'sms';
        $this->cost_per_message = 0.0062;
    }
    
    public function send($recipient, $message, $options = []) {
        $api_key = $this->config['api_key'] ?? '';
        $api_secret = $this->config['api_secret'] ?? '';
        $from = $this->config['from'] ?? 'OTP';
        
        if (empty($api_key) || empty($api_secret)) {
            return ['success' => false, 'message' => 'Vonage configuration incomplete'];
        }
        
        $recipient = $this->format_phone($recipient);
        
        $result = $this->make_request('https://rest.nexmo.com/sms/json', [
            'method' => 'POST',
            'body' => json_encode([
                'api_key' => $api_key,
                'api_secret' => $api_secret,
                'to' => $recipient,
                'from' => $from,
                'text' => $message,
            ]),
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        
        $this->log_attempt($result['success'], $recipient, $result);
        
        if ($result['success']) {
            $data = json_decode($result['body'], true);
            $status = $data['messages'][0]['status'] ?? '1';
            
            if ($status === '0') {
                return ['success' => true, 'message' => 'SMS sent via Vonage', 'response' => $data];
            }
        }
        
        return ['success' => false, 'message' => 'Failed to send SMS via Vonage'];
    }
    
    public function validate_config() {
        $errors = [];
        if (empty($this->config['api_key'])) $errors[] = 'API Key is required';
        if (empty($this->config['api_secret'])) $errors[] = 'API Secret is required';
        return ['valid' => empty($errors), 'errors' => $errors];
    }
}
