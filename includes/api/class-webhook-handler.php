<?php
/**
 * Webhook Handler
 * Triggers webhooks for various OTP events
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Webhook_Handler {
    
    public function __construct() {
        // Hook into events
        add_action('otp_login_pro_otp_sent', [$this, 'trigger_otp_sent'], 10, 3);
        add_action('otp_login_pro_user_logged_in', [$this, 'trigger_login_success'], 10, 1);
        add_action('otp_login_pro_verification_failed', [$this, 'trigger_verification_failed'], 10, 2);
        add_action('otp_login_pro_user_registered', [$this, 'trigger_user_registered'], 10, 1);
    }
    
    /**
     * Trigger webhook
     */
    private function trigger($event, $data) {
        $webhook_url = get_option('otp_login_pro_webhook_url');
        
        if (empty($webhook_url)) {
            return;
        }
        
        $payload = [
            'event' => $event,
            'data' => $data,
            'timestamp' => current_time('mysql'),
            'site_url' => home_url(),
        ];
        
        // Send webhook asynchronously
        wp_remote_post($webhook_url, [
            'blocking' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-OTP-Event' => $event,
                'X-OTP-Signature' => $this->generate_signature($payload),
            ],
            'body' => json_encode($payload),
            'timeout' => 5,
        ]);
        
        do_action('otp_login_pro_webhook_triggered', $event, $data);
    }
    
    /**
     * Generate HMAC signature
     */
    private function generate_signature($payload) {
        $secret = get_option('otp_login_pro_webhook_secret', wp_generate_password(32, false));
        return hash_hmac('sha256', json_encode($payload), $secret);
    }
    
    /**
     * Event: OTP Sent
     */
    public function trigger_otp_sent($identifier, $method, $user) {
        $this->trigger('otp.sent', [
            'identifier' => $identifier,
            'method' => $method,
            'user_id' => $user ? $user->ID : null,
        ]);
    }
    
    /**
     * Event: Login Success
     */
    public function trigger_login_success($user) {
        $this->trigger('otp.verified', [
            'user_id' => $user->ID,
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
        ]);
    }
    
    /**
     * Event: Verification Failed
     */
    public function trigger_verification_failed($identifier, $otp) {
        $this->trigger('otp.failed', [
            'identifier' => $identifier,
            'ip_address' => $this->get_ip(),
        ]);
    }
    
    /**
     * Event: User Registered
     */
    public function trigger_user_registered($user) {
        $this->trigger('user.registered', [
            'user_id' => $user->ID,
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
        ]);
    }
    
    /**
     * Get IP address
     */
    private function get_ip() {
        return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
    }
}

new OTP_Login_Pro_Webhook_Handler();
