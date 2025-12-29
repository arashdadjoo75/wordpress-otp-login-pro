<?php
/**
 * Provider Manager
 * Manages all OTP delivery providers with failover support
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Provider_Manager {
    
    private $providers = [];
    private $active_providers = [];
    
    public function __construct() {
        $this->load_providers();
        $this->init_active_providers();
    }
    
    /**
     * Load all available providers
     */
    private function load_providers() {
        // SMS Providers
        require_once OTP_LOGIN_PRO_INCLUDES . 'providers/class-provider-sms-twilio.php';
        require_once OTP_LOGIN_PRO_INCLUDES . 'providers/class-provider-sms-vonage.php';
        require_once OTP_LOGIN_PRO_INCLUDES . 'providers/class-provider-sms-kavenegar.php';
        
        // Email Providers
        require_once OTP_LOGIN_PRO_INCLUDES . 'providers/class-provider-email-wp-mail.php';
        
        // Register SMS providers
        $this->register_provider('twilio', 'sms', OTP_Login_Pro_Provider_SMS_Twilio::class);
        $this->register_provider('vonage', 'sms', OTP_Login_Pro_Provider_SMS_Vonage::class);
        $this->register_provider('kavenegar', 'sms', OTP_Login_Pro_Provider_SMS_Kavenegar::class);
        
        // Register Email providers
        $this->register_provider('wp_mail', 'email', OTP_Login_Pro_Provider_Email_WP_Mail::class);
        
        do_action('otp_login_pro_providers_loaded', $this);
    }
    
    /**
     * Register a provider
     */
    public function register_provider($id, $type, $class) {
        $this->providers[$id] = [
            'id' => $id,
            'type' => $type,
            'class' => $class,
        ];
    }
    
    /**
     * Initialize active providers based on settings
     */
    private function init_active_providers() {
        foreach ($this->providers as $id => $provider) {
            $config = get_option("otp_login_pro_provider_{$id}", []);
            
            if (!empty($config) && isset($config['enabled']) && $config['enabled']) {
                try {
                    $instance = new $provider['class']($config);
                    if ($instance->is_enabled()) {
                        $this->active_providers[$id] = $instance;
                    }
                } catch (Exception $e) {
                    error_log("Failed to initialize provider {$id}: " . $e->getMessage());
                }
            }
        }
        
        // Sort by priority
        uasort($this->active_providers, function($a, $b) {
            return $a->get_priority() - $b->get_priority();
        });
    }
    
    /**
     * Send OTP with automatic failover
     */
    public function send_otp($recipient, $message, $type = 'sms', $options = []) {
        $providers = $this->get_providers_by_type($type);
        
        if (empty($providers)) {
            return [
                'success' => false,
                'message' => sprintf(__('No %s providers configured', 'otp-login-pro'), $type),
            ];
        }
        
        $last_error = '';
        $failover_enabled = get_option('otp_login_pro_sms_failover', true);
        
        foreach ($providers as $provider_id => $provider) {
            try {
                $result = $provider->send($recipient, $message, $options);
                
                if ($result['success']) {
                    // Log successful delivery
                    $this->log_delivery($recipient, $type, $provider_id, true, $provider->get_cost());
                    return array_merge($result, ['provider' => $provider_id]);
                }
                
                $last_error = $result['message'] ?? 'Unknown error';
                
                // If failover is disabled, stop after first attempt
                if (!$failover_enabled) {
                    break;
                }
                
            } catch (Exception $e) {
                $last_error = $e->getMessage();
                continue;
            }
        }
        
        // All providers failed
        $this->log_delivery($recipient, $type, null, false, 0);
        
        return [
            'success' => false,
            'message' => __('Failed to send OTP', 'otp-login-pro') . ': ' . $last_error,
        ];
    }
    
    /**
     * Get providers by type
     */
    private function get_providers_by_type($type) {
        $filtered = [];
        
        foreach ($this->active_providers as $id => $provider) {
            if ($provider->get_type() === $type) {
                $filtered[$id] = $provider;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Log delivery attempt
     */
    private function log_delivery($recipient, $type, $provider_id, $success, $cost) {
        do_action('otp_login_pro_delivery_attempt', [
            'recipient' => $recipient,
            'type' => $type,
            'provider' => $provider_id,
            'success' => $success,
            'cost' => $cost,
            'timestamp' => current_time('mysql'),
        ]);
    }
    
    /**
     * Get all providers
     */
    public function get_all_providers() {
        return $this->providers;
    }
    
    /**
     * Get active providers
     */
    public function get_active_providers() {
        return $this->active_providers;
    }
    
    /**
     * Get provider instance
     */
    public function get_provider($id) {
        return $this->active_providers[$id] ?? null;
    }
    
    /**
     * Test provider connection
     */
    public function test_provider($id, $test_recipient) {
        $provider = $this->get_provider($id);
        
        if (!$provider) {
            return ['success' => false, 'message' => 'Provider not found'];
        }
        
        $test_message = 'Test message from OTP Login Pro. Code: 123456';
        
        return $provider->send($test_recipient, $test_message, ['test' => true]);
    }
}
