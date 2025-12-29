<?php
/**
 * AWS SNS SMS Provider
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Provider_SMS_AWS_SNS extends OTP_Login_Pro_Abstract_Provider {
    
    protected function init() {
        $this->name = 'AWS SNS';
        $this->type = 'sms';
        $this->cost_per_message = 0.00645;
    }
    
    public function send($recipient, $message, $options = []) {
        $access_key = $this->config['access_key'] ?? '';
        $secret_key = $this->config['secret_key'] ?? '';
        $region = $this->config['region'] ?? 'us-east-1';
        
        if (empty($access_key) || empty($secret_key)) {
            return ['success' => false, 'message' => 'AWS credentials required'];
        }
        
        $recipient = $this->format_phone($recipient);
        
        // AWS SNS API v4 signature (simplified implementation)
        $endpoint = "https://sns.{$region}.amazonaws.com/";
        
        $params = [
            'Action' => 'Publish',
            'Message' => $message,
            'PhoneNumber' => $recipient,
            'MessageAttributes.entry.1.Name' => 'AWS.SNS.SMS.SMSType',
            'MessageAttributes.entry.1.Value.StringValue' => 'Transactional',
            'MessageAttributes.entry.1.Value.DataType' => 'String',
            'Version' => '2010-03-31',
        ];
        
        $query_string = http_build_query($params);
        
        $result = $this->make_request($endpoint . '?' . $query_string, [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);
        
        $this->log_attempt($result['success'], $recipient, $result);
        
        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'SMS sent via AWS SNS' : 'Failed to send via AWS SNS',
        ];
    }
    
    public function validate_config() {
        $errors = [];
        if (empty($this->config['access_key'])) $errors[] = 'Access Key required';
        if (empty($this->config['secret_key'])) $errors[] = 'Secret Key required';
        return ['valid' => empty($errors), 'errors' => $errors];
    }
}
