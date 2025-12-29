<?php
/**
 * Authentication Manager
 * Main controller for OTP authentication flow
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Auth_Manager {
    
    public function __construct() {
        // AJAX hooks
        add_action('wp_ajax_otp_pro_send', [$this, 'ajax_send_otp']);
        add_action('wp_ajax_nopriv_otp_pro_send', [$this, 'ajax_send_otp']);
        add_action('wp_ajax_otp_pro_verify', [$this, 'ajax_verify_otp']);
        add_action('wp_ajax_nopriv_otp_pro_verify', [$this, 'ajax_verify_otp']);
        add_action('wp_ajax_otp_pro_resend', [$this, 'ajax_resend_otp']);
        add_action('wp_ajax_nopriv_otp_pro_resend', [$this, 'ajax_resend_otp']);
    }
    
    /**
     * Send OTP via AJAX
     */
    public function ajax_send_otp() {
        check_ajax_referer('otp_login_pro_nonce', 'nonce');
        
        $identifier = sanitize_text_field($_POST['identifier'] ?? '');
        $method = sanitize_text_field($_POST['method'] ?? 'auto'); // sms, email, auto
        
        $result = $this->send_otp($identifier, $method);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Send OTP
     */
    public function send_otp($identifier, $method = 'auto') {
        global $wpdb;
        
        // Validate identifier
        if (empty($identifier)) {
            return ['success' => false, 'message' => __('Identifier required', 'otp-login-pro')];
        }
        
        // Determine if email or phone
        $is_email = is_email($identifier);
        $is_phone = preg_match('/^[0-9+\s()-]+$/', $identifier);
        
        // Find user
        $user = null;
        if ($is_email) {
            $user = get_user_by('email', $identifier);
        } elseif ($is_phone) {
            // Clean phone number
            $phone_clean = preg_replace('/[^0-9]/', '', $identifier);
            $user = $wpdb->get_row($wpdb->prepare("
                SELECT user_id FROM {$wpdb->prefix}otp_phone_numbers 
                WHERE phone_number LIKE %s AND is_verified = 1 
                LIMIT 1
            ", '%' . $phone_clean));
            
            if ($user) {
                $user = get_user_by('id', $user->user_id);
            } else {
                // Try by login
                $user = get_user_by('login', $identifier);
            }
        }
        
        if (!$user && !get_option('otp_login_pro_registration_enabled', false)) {
            return ['success' => false, 'message' => __('User not found', 'otp-login-pro')];
        }
        
        // Rate limiting check
        $rate_limit_check = $this->check_rate_limit($identifier);
        if (!$rate_limit_check['allowed']) {
            return ['success' => false, 'message' => $rate_limit_check['message']];
        }
        
        // Generate OTP
        $otp_length = intval(get_option('otp_login_pro_otp_length', 6));
        $otp_type = get_option('otp_login_pro_otp_type', 'numeric');
        $otp_code = OTP_Login_Pro_OTP_Generator::generate($otp_length, $otp_type);
        $otp_hash = OTP_Login_Pro_OTP_Generator::hash($otp_code);
        
        // Determine delivery method
        if ($method === 'auto') {
            $method = $is_email ? 'email' : 'sms';
        }
        
        // Prepare message
        $template = get_option('otp_login_pro_sms_template', 'Your login code is: {otp}');
        $message = str_replace('{otp}', $otp_code, $template);
        $expiry_minutes = intval(get_option('otp_login_pro_expiry', 300)) / 60;
        $message = str_replace('{expiry}', $expiry_minutes, $message);
        
        // Send via provider
        $provider_manager = new OTP_Login_Pro_Provider_Manager();
        $send_result = $provider_manager->send_otp($identifier, $message, $method, [
            'otp' => $otp_code,
            'user_id' => $user ? $user->ID : null,
        ]);
        
        if (!$send_result['success']) {
            return $send_result;
        }
        
        // Store OTP in database
        $expiry = intval(get_option('otp_login_pro_expiry', 300));
        $otp_id = $wpdb->insert(
            "{$wpdb->prefix}otp_logs",
            [
                'user_id' => $user ? $user->ID : null,
                'identifier' => $identifier,
                'otp_hash' => $otp_hash,
                'method' => $method,
                'provider' => $send_result['provider'] ?? 'unknown',
                'status' => 'sent',
                'ip_address' => $this->get_ip_address(),
                'user_agent' => $this->get_user_agent(),
                'expires_at' => date('Y-m-d H:i:s', time() + $expiry),
                'sent_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        // Update rate limit
        $this->update_rate_limit($identifier);
        
        // Create session
        $session_key = OTP_Login_Pro_Session_Manager::create($identifier, [
            'otp_id' => $wpdb->insert_id,
            'method' => $method,
        ]);
        
        do_action('otp_login_pro_otp_sent', $identifier, $method, $user);
        
        return [
            'success' => true,
            'message' => sprintf(__('OTP sent via %s', 'otp-login-pro'), $method),
            'session_key' => $session_key,
            'expires_in' => $expiry,
        ];
    }
    
    /**
     * Verify OTP via AJAX
     */
    public function ajax_verify_otp() {
        check_ajax_referer('otp_login_pro_nonce', 'nonce');
        
        $identifier = sanitize_text_field($_POST['identifier'] ?? '');
        $otp_code = sanitize_text_field($_POST['otp'] ?? '');
        $remember = isset($_POST['remember']) && $_POST['remember'] === 'true';
        
        $result = $this->verify_otp($identifier, $otp_code, $remember);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Verify OTP
     */
    public function verify_otp($identifier, $otp_code, $remember = false) {
        $validation = OTP_Login_Pro_OTP_Validator::validate($identifier, $otp_code);
        
        if (!$validation['valid']) {
            do_action('otp_login_pro_verification_failed', $identifier, $otp_code);
            return ['success' => false, 'message' => $validation['message']];
        }
        
        // Get user
        $user = null;
        if ($validation['user_id']) {
            $user = get_user_by('id', $validation['user_id']);
        } else {
            // Check if registration is enabled
            if (get_option('otp_login_pro_registration_enabled', false)) {
                // Auto-create user
                $user = $this->auto_register_user($identifier);
            }
        }
        
        if (!$user) {
            return ['success' => false, 'message' => __('User not found', 'otp-login-pro')];
        }
        
        // Log the user in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);
        do_action('wp_login', $user->user_login, $user);
        do_action('otp_login_pro_user_logged_in', $user);
        
        // Trust device if enabled
        if ($remember && get_option('otp_login_pro_trusted_devices', false)) {
            $this->trust_device($user->ID);
        }
        
        return [
            'success' => true,
            'message' => __('Login successful', 'otp-login-pro'),
            'redirect' => apply_filters('otp_login_pro_redirect_url', home_url(), $user),
            'user_id' => $user->ID,
        ];
    }
    
    /**
     * Auto-register user
     */
    private function auto_register_user($identifier) {
        $is_email = is_email($identifier);
        
        if (!$is_email) {
            return null; // Can't register without email
        }
        
        $username = sanitize_user(substr($identifier, 0, strpos($identifier, '@')));
        $username = $username . '_' . wp_rand(1000, 9999);
        
        $user_id = wp_create_user($username, wp_generate_password(), $identifier);
        
        if (is_wp_error($user_id)) {
            return null;
        }
        
        $user = get_user_by('id', $user_id);
        
        // Set role
        $role = get_option('otp_login_pro_registration_role', 'subscriber');
        $user->set_role($role);
        
        do_action('otp_login_pro_user_registered', $user);
        
        return $user;
    }
    
    /**
     * Resend OTP
     */
    public function ajax_resend_otp() {
        check_ajax_referer('otp_login_pro_nonce', 'nonce');
        
        $identifier = sanitize_text_field($_POST['identifier'] ?? '');
        $method = sanitize_text_field($_POST['method'] ?? 'auto');
        
        // Check cooldown
        $cooldown = intval(get_option('otp_login_pro_cooldown', 60));
        $last_sent = $this->get_last_sent_time($identifier);
        
        if ($last_sent && (time() - $last_sent) < $cooldown) {
            $wait_time = $cooldown - (time() - $last_sent);
            wp_send_json_error([
                'message' => sprintf(__('Please wait %d seconds before resending', 'otp-login-pro'), $wait_time),
                'wait_time' => $wait_time,
            ]);
        }
        
        // Invalidate old OTPs
        OTP_Login_Pro_OTP_Validator::invalidate($identifier);
        
        // Send new OTP
        $result = $this->send_otp($identifier, $method);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Check rate limit
     */
    private function check_rate_limit($identifier) {
        global $wpdb;
        
        if (!get_option('otp_login_pro_rate_limit_enabled', true)) {
            return ['allowed' => true];
        }
        
        $ip = $this->get_ip_address();
        $limit_requests = intval(get_option('otp_login_pro_rate_limit_requests', 5));
        $limit_window = intval(get_option('otp_login_pro_rate_limit_window', 300));
        
        $rate_limit = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}otp_rate_limits 
            WHERE identifier = %s AND ip_address = %s
        ", $identifier, $ip));
        
        if ($rate_limit) {
            // Check if blocked
            if ($rate_limit->blocked_until && strtotime($rate_limit->blocked_until) > time()) {
                $wait_time = strtotime($rate_limit->blocked_until) - time();
                return [
                    'allowed' => false,
                    'message' => sprintf(__('Too many requests. Please wait %d minutes', 'otp-login-pro'), ceil($wait_time / 60)),
                ];
            }
            
            // Check if within window
            if (strtotime($rate_limit->last_attempt) > (time() - $limit_window)) {
                if ($rate_limit->attempt_count >= $limit_requests) {
                    // Block for 1 hour
                    $wpdb->update(
                        "{$wpdb->prefix}otp_rate_limits",
                        ['blocked_until' => date('Y-m-d H:i:s', time() + 3600)],
                        ['id' => $rate_limit->id]
                    );
                    
                    return [
                        'allowed' => false,
                        'message' => __('Too many requests. Account temporarily blocked', 'otp-login-pro'),
                    ];
                }
            } else {
                // Reset counter
                $wpdb->update(
                    "{$wpdb->prefix}otp_rate_limits",
                    ['attempt_count' => 0],
                    ['id' => $rate_limit->id]
                );
            }
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Update rate limit
     */
    private function update_rate_limit($identifier) {
        global $wpdb;
        
        $ip = $this->get_ip_address();
        
        $wpdb->query($wpdb->prepare("
            INSERT INTO {$wpdb->prefix}otp_rate_limits 
            (identifier, ip_address, attempt_count, last_attempt)
            VALUES (%s, %s, 1, NOW())
            ON DUPLICATE KEY UPDATE 
            attempt_count = attempt_count + 1,
            last_attempt = NOW()
        ", $identifier, $ip));
    }
    
    /**
     * Get last sent time
     */
    private function get_last_sent_time($identifier) {
        global $wpdb;
        
        $last_sent = $wpdb->get_var($wpdb->prepare("
            SELECT UNIX_TIMESTAMP(sent_at) FROM {$wpdb->prefix}otp_logs 
            WHERE identifier = %s 
            ORDER BY sent_at DESC 
            LIMIT 1
        ", $identifier));
        
        return $last_sent ? intval($last_sent) : null;
    }
    
    /**
     * Trust device
     */
    private function trust_device($user_id) {
        global $wpdb;
        
        $device_token = wp_generate_password(40, false);
        $device_fingerprint = $this->get_device_fingerprint();
        $duration = intval(get_option('otp_login_pro_trusted_duration', 30));
        
        $wpdb->insert(
            "{$wpdb->prefix}otp_trusted_devices",
            [
                'user_id' => $user_id,
                'device_token' => $device_token,
                'device_fingerprint' => $device_fingerprint,
                'ip_address' => $this->get_ip_address(),
                'user_agent' => $this->get_user_agent(),
                'last_used' => current_time('mysql'),
                'trusted_at' => current_time('mysql'),
                'expires_at' => date('Y-m-d H:i:s', time() + ($duration * DAY_IN_SECONDS)),
            ]
        );
        
        setcookie('otp_device_token', $device_token, time() + ($duration * DAY_IN_SECONDS), '/');
    }
    
    /**
     * Helper functions
     */
    private function get_ip_address() {
        return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
    }
    
    private function get_user_agent() {
        return sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');
    }
    
    private function get_device_fingerprint() {
        return md5($this->get_user_agent() . $this->get_ip_address());
    }
}
