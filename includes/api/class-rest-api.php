<?php
/**
 * REST API - Complete API endpoints for OTP Login Pro
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_REST_API {
    
    protected $namespace = 'otp-pro/v1';
    
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    public function register_routes() {
        // Send OTP
        register_rest_route($this->namespace, '/send', [
            'methods' => 'POST',
            'callback' => [$this, 'send_otp'],
            'permission_callback' => '__return_true',
            'args' => [
                'identifier' => ['required' => true, 'type' => 'string'],
                'method' => ['default' => 'auto', 'type' => 'string'],
            ],
        ]);
        
        // Verify OTP
        register_rest_route($this->namespace, '/verify', [
            'methods' => 'POST',
            'callback' => [$this, 'verify_otp'],
            'permission_callback' => '__return_true',
            'args' => [
                'identifier' => ['required' => true, 'type' => 'string'],
                'otp' => ['required' => true, 'type' => 'string'],
                'remember' => ['default' => false, 'type' => 'boolean'],
            ],
        ]);
        
        // Resend OTP
        register_rest_route($this->namespace, '/resend', [
            'methods' => 'POST',
            'callback' => [$this, 'resend_otp'],
            'permission_callback' => '__return_true',
            'args' => [
                'identifier' => ['required' => true, 'type' => 'string'],
            ],
        ]);
        
        // Get Analytics
        register_rest_route($this->namespace, '/analytics', [
            'methods' => 'GET',
            'callback' => [$this, 'get_analytics'],
            'permission_callback' => [$this, 'check_admin_permission'],
            'args' => [
                'period' => ['default' => '7days', 'type' => 'string'],
            ],
        ]);
        
        // Get Status
        register_rest_route($this->namespace, '/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_status'],
            'permission_callback' => '__return_true',
            'args' => [
                'identifier' => ['required' => true, 'type' => 'string'],
            ],
        ]);
    }
    
    public function send_otp($request) {
        $identifier = $request['identifier'];
        $method = $request['method'];
        
        $auth_manager = new OTP_Login_Pro_Auth_Manager();
        $result = $auth_manager->send_otp($identifier, $method);
        
        if ($result['success']) {
            return new WP_REST_Response($result, 200);
        } else {
            return new WP_Error('otp_send_failed', $result['message'], ['status' => 400]);
        }
    }
    
    public function verify_otp($request) {
        $identifier = $request['identifier'];
        $otp = $request['otp'];
        $remember = $request['remember'];
        
        $auth_manager = new OTP_Login_Pro_Auth_Manager();
        $result = $auth_manager->verify_otp($identifier, $otp, $remember);
        
        if ($result['success']) {
            return new WP_REST_Response($result, 200);
        } else {
            return new WP_Error('otp_verify_failed', $result['message'], ['status' => 400]);
        }
    }
    
    public function resend_otp($request) {
        $identifier = $request['identifier'];
        
        OTP_Login_Pro_OTP_Validator::invalidate($identifier);
        
        $auth_manager = new OTP_Login_Pro_Auth_Manager();
        $result = $auth_manager->send_otp($identifier);
        
        if ($result['success']) {
            return new WP_REST_Response($result, 200);
        } else {
            return new WP_Error('otp_resend_failed', $result['message'], ['status' => 400]);
        }
    }
    
    public function get_analytics($request) {
        $period = $request['period'];
        
        $analytics = new OTP_Login_Pro_Analytics();
        $stats = $analytics->get_stats($period);
        $daily = $analytics->get_daily_stats(7);
        
        return new WP_REST_Response([
            'stats' => $stats,
            'daily' => $daily,
        ], 200);
    }
    
    public function get_status($request) {
        $identifier = $request['identifier'];
        
        $exists = OTP_Login_Pro_OTP_Validator::exists($identifier);
        
        return new WP_REST_Response([
            'has_pending_otp' => $exists,
        ], 200);
    }
    
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }
}
