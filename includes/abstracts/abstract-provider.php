<?php
/**
 * Abstract Provider Class
 * Base class for all OTP delivery providers (SMS, Email, WhatsApp, etc.)
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class OTP_Login_Pro_Abstract_Provider {
    
    protected $name;
    protected $type; // sms, email, whatsapp, voice, etc.
    protected $config = [];
    protected $cost_per_message = 0.00;
    protected $is_enabled = false;
    protected $priority = 10;
    
    /**
     * Constructor
     */
    public function __construct($config = []) {
        $this->config = $config;
        $this->is_enabled = isset($config['enabled']) ? (bool) $config['enabled'] : false;
        $this->priority = isset($config['priority']) ? intval($config['priority']) : 10;
        $this->init();
    }
    
    /**
     * Initialize provider
     */
    abstract protected function init();
    
    /**
     * Send OTP
     * 
     * @param string $recipient Phone number or email
     * @param string $message The OTP message
     * @param array $options Additional options
     * @return array ['success' => bool, 'message' => string, 'response' => mixed]
     */
    abstract public function send($recipient, $message, $options = []);
    
    /**
     * Validate configuration
     * 
     * @return array ['valid' => bool, 'errors' => array]
     */
    abstract public function validate_config();
    
    /**
     * Get account balance/credits (if supported)
     * 
     * @return mixed
     */
    public function get_balance() {
        return null;
    }
    
    /**
     * Format phone number
     */
    protected function format_phone($phone, $country_code = '') {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present
        if ($country_code && substr($phone, 0, strlen($country_code)) !== $country_code) {
            $phone = $country_code . ltrim($phone, '0');
        }
        
        return '+' . $phone;
    }
    
    /**
     * Log send attempt
     */
    protected function log_attempt($success, $recipient, $response = null, $error = null) {
        do_action('otp_login_pro_provider_attempt', [
            'provider' => $this->name,
            'type' => $this->type,
            'success' => $success,
            'recipient' => $recipient,
            'response' => $response,
            'error' => $error,
            'timestamp' => current_time('mysql'),
        ]);
    }
    
    /**
     * Get provider name
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Get provider type
     */
    public function get_type() {
        return $this->type;
    }
    
    /**
     * Get cost per message
     */
    public function get_cost() {
        return $this->cost_per_message;
    }
    
    /**
     * Is enabled
     */
    public function is_enabled() {
        return $this->is_enabled;
    }
    
    /**
     * Get priority
     */
    public function get_priority() {
        return $this->priority;
    }
    
    /**
     * Make HTTP request
     */
    protected function make_request($url, $args = []) {
        $defaults = [
            'timeout' => 30,
            'headers' => [],
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        return [
            'success' => $code >= 200 && $code < 300,
            'code' => $code,
            'body' => $body,
            'response' => $response,
        ];
    }
}
