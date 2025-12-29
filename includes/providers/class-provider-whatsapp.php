<?php
/**
 * WhatsApp Business API Provider
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Provider_WhatsApp extends OTP_Login_Pro_Abstract_Provider {
    
    protected function init() {
        $this->name = 'WhatsApp';
        $this->type = 'whatsapp';
        $this->cost_per_message = 0.005;
    }
    
    public function send($recipient, $message, $options = []) {
        $phone_number_id = $this->config['phone_number_id'] ?? '';
        $access_token = $this->config['access_token'] ?? '';
        
        if (empty($phone_number_id) || empty($access_token)) {
            return ['success' => false, 'message' => 'WhatsApp credentials required'];
        }
        
        $recipient = $this->format_phone($recipient);
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $recipient,
            'type' => 'text',
            'text' => [
                'body' => $message,
            ],
        ];
        
        $url = "https://graph.facebook.com/v17.0/{$phone_number_id}/messages";
        
        $result = $this->make_request($url, [
            'method' => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($payload),
        ]);
        
        $this->log_attempt($result['success'], $recipient, $result);
        
        if ($result['success']) {
            $data = json_decode($result['body'], true);
            if (isset($data['messages'])) {
                return ['success' => true, 'message' => 'Message sent via WhatsApp', 'response' => $data];
            }
        }
        
        return ['success' => false, 'message' => 'Failed to send via WhatsApp'];
    }
    
    public function validate_config() {
        $errors = [];
        if (empty($this->config['phone_number_id'])) $errors[] = 'Phone Number ID required';
        if (empty($this->config['access_token'])) $errors[] = 'Access Token required';
        return ['valid' => empty($errors), 'errors' => $errors];
    }
}
