<?php
/**
 * WebAuthn / Biometric Authentication
 * Passwordless authentication using FIDO2
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_WebAuthn {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_webauthn_register', [$this, 'ajax_register']);
        add_action('wp_ajax_webauthn_authenticate', [$this, 'ajax_authenticate']);
        add_action('wp_ajax_nopriv_webauthn_authenticate', [$this, 'ajax_authenticate']);
    }
    
    /**
     * Enqueue WebAuthn scripts
     */
    public function enqueue_scripts() {
        if (!get_option('otp_login_pro_webauthn_enabled', false)) {
            return;
        }
        
        wp_register_script(
            'otp-webauthn',
            OTP_LOGIN_PRO_URL . 'assets/js/webauthn.js',
            ['jquery'],
            OTP_LOGIN_PRO_VERSION,
            true
        );
        
        wp_localize_script('otp-webauthn', 'otpWebAuthn', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('otp_webauthn'),
            'rpName' => get_bloginfo('name'),
            'rpId' => parse_url(home_url(), PHP_URL_HOST),
        ]);
        
        wp_enqueue_script('otp-webauthn');
    }
    
    /**
     * Register new credential
     */
    public function ajax_register() {
        check_ajax_referer('otp_webauthn', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        $user_id = get_current_user_id();
        
        // Generate challenge
        $challenge = $this->generate_challenge();
        
        // Store challenge temporarily
        set_transient('webauthn_challenge_' . $user_id, $challenge, 300);
        
        // Get user info
        $user = get_userdata($user_id);
        
        $publicKey = [
            'challenge' => $challenge,
            'rp' => [
                'name' => get_bloginfo('name'),
                'id' => parse_url(home_url(), PHP_URL_HOST),
            ],
            'user' => [
                'id' => base64_encode($user_id),
                'name' => $user->user_login,
                'displayName' => $user->display_name,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7], // ES256
                ['type' => 'public-key', 'alg' => -257], // RS256
            ],
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'platform',
                'requireResidentKey' => false,
                'userVerification' => 'preferred',
            ],
            'timeout' => 60000,
            'attestation' => 'none',
        ];
        
        wp_send_json_success(['publicKey' => $publicKey]);
    }
    
    /**
     * Verify and store credential
     */
    public function ajax_verify_registration() {
        check_ajax_referer('otp_webauthn', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'User not logged in']);
        }
        
        $user_id = get_current_user_id();
        $credential = json_decode(stripslashes($_POST['credential']), true);
        
        // Verify challenge
        $stored_challenge = get_transient('webauthn_challenge_' . $user_id);
        
        if (!$stored_challenge) {
            wp_send_json_error(['message' => 'Challenge expired']);
        }
        
        // Store credential
        global $wpdb;
        
        $wpdb->insert(
            "{$wpdb->prefix}otp_webauthn_credentials",
            [
                'user_id' => $user_id,
                'credential_id' => $credential['id'],
                'public_key' => $credential['publicKey'],
                'counter' => 0,
                'created_at' => current_time('mysql'),
            ]
        );
        
        delete_transient('webauthn_challenge_' . $user_id);
        
        wp_send_json_success(['message' => 'Credential registered successfully']);
    }
    
    /**
     * Authenticate with WebAuthn
     */
    public function ajax_authenticate() {
        check_ajax_referer('otp_webauthn', 'nonce');
        
        $user_login = sanitize_text_field($_POST['username'] ?? '');
        
        if (empty($user_login)) {
            wp_send_json_error(['message' => 'Username required']);
        }
        
        $user = get_user_by('login', $user_login);
        
        if (!$user) {
            wp_send_json_error(['message' => 'User not found']);
        }
        
        // Generate challenge
        $challenge = $this->generate_challenge();
        set_transient('webauthn_auth_challenge_' . $user->ID, $challenge, 300);
        
        // Get user's credentials
        global $wpdb;
        $credentials = $wpdb->get_results($wpdb->prepare("
            SELECT credential_id FROM {$wpdb->prefix}otp_webauthn_credentials 
            WHERE user_id = %d
        ", $user->ID));
        
        if (empty($credentials)) {
            wp_send_json_error(['message' => 'No credentials found']);
        }
        
        $allowCredentials = array_map(function($cred) {
            return [
                'type' => 'public-key',
                'id' => $cred->credential_id,
            ];
        }, $credentials);
        
        $publicKey = [
            'challenge' => $challenge,
            'timeout' => 60000,
            'rpId' => parse_url(home_url(), PHP_URL_HOST),
            'allowCredentials' => $allowCredentials,
            'userVerification' => 'preferred',
        ];
        
        wp_send_json_success(['publicKey' => $publicKey, 'userId' => $user->ID]);
    }
    
    /**
     * Generate random challenge
     */
    private function generate_challenge() {
        return base64_encode(random_bytes(32));
    }
    
    /**
     * Add WebAuthn table on activation
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'otp_webauthn_credentials';
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            credential_id text NOT NULL,
            public_key text NOT NULL,
            counter bigint(20) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_used datetime NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) {$wpdb->get_charset_collate()};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize
new OTP_Login_Pro_WebAuthn();

// Create table on activation
register_activation_hook(OTP_LOGIN_PRO_FILE, ['OTP_Login_Pro_WebAuthn', 'create_table']);
