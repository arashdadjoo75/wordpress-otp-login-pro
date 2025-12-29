<?php
// Email Provider (WordPress wp_mail)
if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Provider_Email_WP_Mail extends OTP_Login_Pro_Abstract_Provider {
    
    protected function init() {
        $this->name = 'WordPress Mail';
        $this->type = 'email';
        $this->cost_per_message = 0.00;
    }
    
    public function send($recipient, $message, $options = []) {
        $subject = $options['subject'] ?? get_option('otp_loginpro_email_subject', __('Your Login Code', 'otp-login-pro'));
        $from_name = $this->config['from_name'] ?? get_bloginfo('name');
        $from_email = $this->config['from_email'] ?? get_bloginfo('admin_email');
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            "From: {$from_name} <{$from_email}>",
        ];
        
        // Get email template
        $template = $this->get_template($message, $options);
        
        $sent = wp_mail($recipient, $subject, $template, $headers);
        
        $this->log_attempt($sent, $recipient);
        
        return [
            'success' => $sent,
            'message' => $sent ? 'Email sent successfully' : 'Failed to send email',
        ];
    }
    
    private function get_template($otp_code, $options = []) {
        $template_id = get_option('otp_login_pro_email_template', 'default');
        
        $template_file = OTP_LOGIN_PRO_TEMPLATES . "emails/{$template_id}.php";
        
        if (!file_exists($template_file)) {
            $template_file = OTP_LOGIN_PRO_TEMPLATES . 'emails/default.php';
        }
        
        if (file_exists($template_file)) {
            ob_start();
            include $template_file;
            return ob_get_clean();
        }
        
        // Fallback HTML template
        return $this->get_default_template($otp_code, $options);
    }
    
    private function get_default_template($otp_code, $options = []) {
        $site_name = get_bloginfo('name');
        $expiry = intval(get_option('otp_login_pro_expiry', 300)) / 60;
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 40px; border-radius: 0 0 10px 10px; }
                .otp-code { background: white; font-size: 32px; font-weight: bold; letter-spacing: 5px; padding: 20px; text-align: center; border: 2px dashed #667eea; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; color: #999; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$site_name}</h1>
                    <p>Login Verification Code</p>
                </div>
                <div class='content'>
                    <p>Hello,</p>
                    <p>You requested a one-time password to login to your account. Use the code below:</p>
                    <div class='otp-code'>{$otp_code}</div>
                    <p>This code will expire in <strong>{$expiry} minutes</strong>.</p>
                    <p>If you didn't request this code, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " {$site_name}. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    public function validate_config() {
        return ['valid' => true, 'errors' => []];
    }
}
