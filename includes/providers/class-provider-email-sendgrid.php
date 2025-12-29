<?php
/**
 * SendGrid Email Provider
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Provider_Email_SendGrid extends OTP_Login_Pro_Abstract_Provider {
    
    protected function init() {
        $this->name = 'SendGrid';
        $this->type = 'email';
        $this->cost_per_message = 0.001;
    }
    
    public function send($recipient, $message, $options = []) {
        $api_key = $this->config['api_key'] ?? '';
        $from_email = $this->config['from_email'] ?? get_bloginfo('admin_email');
        $from_name = $this->config['from_name'] ?? get_bloginfo('name');
        
        if (empty($api_key)) {
            return ['success' => false, 'message' => 'SendGrid API key required'];
        }
        
        $subject = $options['subject'] ?? __('Your Login Code', 'otp-login-pro');
        
        // Get HTML template
        $html_content = $this->get_html_content($message, $options);
        
        $payload = [
            'personalizations' => [[
                'to' => [['email' => $recipient]],
                'subject' => $subject,
            ]],
            'from' => [
                'email' => $from_email,
                'name' => $from_name,
            ],
            'content' => [[
                'type' => 'text/html',
                'value' => $html_content,
            ]],
        ];
        
        $result = $this->make_request('https://api.sendgrid.com/v3/mail/send', [
            'method' => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($payload),
        ]);
        
        $this->log_attempt($result['success'], $recipient, $result);
        
        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'Email sent via SendGrid' : 'Failed to send via SendGrid',
            'response' => $result,
        ];
    }
    
    private function get_html_content($otp, $options) {
        $template_file = OTP_LOGIN_PRO_TEMPLATES . 'emails/default.php';
        
        if (file_exists($template_file)) {
            ob_start();
            $otp_code = $otp;
            include $template_file;
            return ob_get_clean();
        }
        
        return "Your OTP code is: <strong>{$otp}</strong>";
    }
    
    public function validate_config() {
        return [
            'valid' => !empty($this->config['api_key']),
            'errors' => empty($this->config['api_key']) ? ['API Key required'] : [],
        ];
    }
}
