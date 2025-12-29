<?php
/**
 * Plugin Name: OTP Login Pro
 * Plugin URI: https://example.com/otp-login-pro
 * Description: Enterprise-grade OTP authentication system with 150+ features including SMS, Email, WhatsApp, Voice OTP, 2FA/MFA, fraud detection, and deep WordPress ecosystem integration.
 * Version: 2.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: otp-login-pro
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('OTP_LOGIN_PRO_VERSION', '2.0.0');
define('OTP_LOGIN_PRO_FILE', __FILE__);
define('OTP_LOGIN_PRO_PATH', plugin_dir_path(__FILE__));
define('OTP_LOGIN_PRO_URL', plugin_dir_url(__FILE__));
define('OTP_LOGIN_PRO_BASENAME', plugin_basename(__FILE__));
define('OTP_LOGIN_PRO_INCLUDES', OTP_LOGIN_PRO_PATH . 'includes/');
define('OTP_LOGIN_PRO_ASSETS_URL', OTP_LOGIN_PRO_URL . 'assets/');
define('OTP_LOGIN_PRO_TEMPLATES', OTP_LOGIN_PRO_PATH . 'templates/');

// Autoloader
spl_autoload_register(function ($class) {
    // Only autoload our classes
    if (strpos($class, 'OTP_Login_Pro_') !== 0) {
        return;
    }

    // Convert class name to file path
    $class_file = strtolower(str_replace('_', '-', $class));
    $class_file = str_replace('otp-login-pro-', '', $class_file);
    
    // Determine subdirectory
    $subdirs = [
        'abstract' => 'abstracts/',
        'provider' => 'providers/',
        'auth' => 'auth/',
        'security' => 'security/',
        'user' => 'user/',
        'integration' => 'integrations/',
        'admin' => 'admin/',
        'api' => 'api/',
        'widget' => 'widgets/',
    ];
    
    $subdir = '';
    foreach ($subdirs as $prefix => $dir) {
        if (strpos($class_file, $prefix) !== false) {
            $subdir = $dir;
            break;
        }
    }
    
    $file = OTP_LOGIN_PRO_INCLUDES . $subdir . 'class-' . $class_file . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Main plugin class
 */
final class OTP_Login_Pro {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Core components
     */
    public $installer;
    public $settings;
    public $providers;
    public $auth;
    public $security;
    public $user_manager;
    public $analytics;
    public $integrations;
    public $api;
    
    /**
     * Get instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->init_components();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(OTP_LOGIN_PRO_FILE, [$this, 'activate']);
        register_deactivation_hook(OTP_LOGIN_PRO_FILE, [$this, 'deactivate']);
        
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Core
        require_once OTP_LOGIN_PRO_INCLUDES . 'class-installer.php';
        require_once OTP_LOGIN_PRO_INCLUDES . 'class-core.php';
        
        $this->installer = new OTP_Login_Pro_Installer();
        
        // Initialize after plugins loaded
        add_action('plugins_loaded', function() {
            $this->settings = new OTP_Login_Pro_Core();
            $this->load_modules();
        }, 5);
    }
    
    /**
     * Load all modules
     */
    private function load_modules() {
        // Abstracts
        require_once OTP_LOGIN_PRO_INCLUDES . 'abstracts/abstract-provider.php';
        require_once OTP_LOGIN_PRO_INCLUDES . 'abstracts/abstract-gateway.php';
        
        // Gateway Adapter (for 118 Iranian gateways)
        if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'providers/class-gateway-adapter.php')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'providers/class-gateway-adapter.php';
        }
        
        // Providers Manager
        require_once OTP_LOGIN_PRO_INCLUDES . 'providers/class-provider-manager.php';
        $this->providers = new OTP_Login_Pro_Provider_Manager();
        
        // Authentication
        require_once OTP_LOGIN_PRO_INCLUDES . 'auth/class-otp-generator.php';
        require_once OTP_LOGIN_PRO_INCLUDES . 'auth/class-otp-validator.php';
        require_once OTP_LOGIN_PRO_INCLUDES . 'auth/class-session-manager.php';
        require_once OTP_LOGIN_PRO_INCLUDES . 'auth/class-auth-manager.php';
        $this->auth = new OTP_Login_Pro_Auth_Manager();
        
        // Advanced Auth (TOTP, Backup Codes, WebAuthn)
        if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'auth/class-totp-manager.php')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'auth/class-totp-manager.php';
        }
        if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'auth/class-backup-codes.php')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'auth/class-backup-codes.php';
        }
        if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'auth/class-webauthn.php')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'auth/class-webauthn.php';
        }
        
        // Security
        require_once OTP_LOGIN_PRO_INCLUDES . 'security/class-rate-limiter.php';
        require_once OTP_LOGIN_PRO_INCLUDES . 'security/class-security-manager.php';
        $this->security = new OTP_Login_Pro_Security_Manager();
        
        // Advanced Security
        if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'security/class-security-config.php')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'security/class-security-config.php';
        }
        if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'security/class-device-manager.php')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'security/class-device-manager.php';
        }
        if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'security/class-fraud-detection.php')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'security/class-fraud-detection.php';
        }
        if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'security/class-geoip.php')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'security/class-geoip.php';
        }
        
        // User Management
        require_once OTP_LOGIN_PRO_INCLUDES . 'user/class-registration.php';
        require_once OTP_LOGIN_PRO_INCLUDES . 'user/class-user-manager.php';
        $this->user_manager = new OTP_Login_Pro_User_Manager();
        
        // Analytics
        require_once OTP_LOGIN_PRO_INCLUDES . 'admin/class-analytics.php';
        $this->analytics = new OTP_Login_Pro_Analytics();
        
        // Advanced Analytics
        if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'admin/class-advanced-analytics.php')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'admin/class-advanced-analytics.php';
        }
        
        // Admin
        if (is_admin()) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'admin/class-admin.php';
            new OTP_Login_Pro_Admin();
            
            // Gateway Selector
            if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'admin/class-gateway-selector.php')) {
                require_once OTP_LOGIN_PRO_INCLUDES . 'admin/class-gateway-selector.php';
            }
            
            // Export
            if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'admin/class-export.php')) {
                require_once OTP_LOGIN_PRO_INCLUDES . 'admin/class-export.php';
            }
        }
        
        // API
        require_once OTP_LOGIN_PRO_INCLUDES . 'api/class-rest-api.php';
        $this->api = new OTP_Login_Pro_REST_API();
        
        // Webhooks
        if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'api/class-webhook-handler.php')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'api/class-webhook-handler.php';
        }
        
        // Performance & Production
        if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'class-performance.php')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'class-performance.php';
        }
        if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'class-production-config.php')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'class-production-config.php';
        }
        
        // Credits & License
        if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'class-credits-manager.php')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'class-credits-manager.php';
        }
        if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'class-license-manager.php')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'class-license-manager.php';
        }
        
        // Advanced Features
        if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'class-multisite.php')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'class-multisite.php';
        }
        if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'class-white-label.php')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'class-white-label.php';
        }
        if (file_exists(OTP_LOGIN_PRO_INCLUDES . 'class-ab-testing.php')) {
            require_once OTP_LOGIN_PRO_INCLUDES . 'class-ab-testing.php';
        }
        
        // Integrations
        require_once OTP_LOGIN_PRO_INCLUDES . 'integrations/class-integration-manager.php';
        $this->integrations = new OTP_Login_Pro_Integration_Manager();
        
        // Shortcodes & Widgets
        require_once OTP_LOGIN_PRO_INCLUDES . 'class-shortcodes.php';
        new OTP_Login_Pro_Shortcodes();
        
        require_once OTP_LOGIN_PRO_INCLUDES . 'widgets/class-widgets.php';
        new OTP_Login_Pro_Widgets();
    }
    
    /**
     * Activation
     */
    public function activate() {
        $this->installer->activate();
        flush_rewrite_rules();
    }
    
    /**
     * Deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'otp-login-pro',
            false,
            dirname(OTP_LOGIN_PRO_BASENAME) . '/languages'
        );
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        do_action('otp_login_pro_init');
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        $theme = get_option('otp_login_pro_theme', 'modern');
        
        // CSS
        wp_enqueue_style(
            'otp-login-pro-frontend',
            OTP_LOGIN_PRO_ASSETS_URL . 'css/frontend.css',
            [],
            OTP_LOGIN_PRO_VERSION
        );
        
        wp_enqueue_style(
            'otp-login-pro-theme',
            OTP_LOGIN_PRO_ASSETS_URL . 'css/themes/' . $theme . '.css',
            ['otp-login-pro-frontend'],
            OTP_LOGIN_PRO_VERSION
        );
        
        // RTL support
        if (is_rtl()) {
            wp_enqueue_style(
                'otp-login-pro-rtl',
                OTP_LOGIN_PRO_ASSETS_URL . 'css/rtl.css',
                ['otp-login-pro-frontend'],
                OTP_LOGIN_PRO_VERSION
            );
        }
        
        // JavaScript
        wp_enqueue_script(
            'otp-login-pro-frontend',
            OTP_LOGIN_PRO_ASSETS_URL . 'js/frontend.js',
            ['jquery'],
            OTP_LOGIN_PRO_VERSION,
            true
        );
        
        wp_localize_script('otp-login-pro-frontend', 'otpLoginPro', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('otp-pro/v1/'),
            'nonce' => wp_create_nonce('otp_login_pro_nonce'),
            'i18n' => [
                'sending' => __('Sending OTP...', 'otp-login-pro'),
                'verifying' => __('Verifying...', 'otp-login-pro'),
                'success' => __('Success!', 'otp-login-pro'),
                'error' => __('An error occurred', 'otp-login-pro'),
                'resend' => __('Resend OTP', 'otp-login-pro'),
                'wait' => __('Please wait %d seconds', 'otp-login-pro'),
            ],
            'settings' => [
                'otp_length' => get_option('otp_login_pro_otp_length', 6),
                'expiry' => get_option('otp_login_pro_expiry', 300),
                'cooldown' => get_option('otp_login_pro_cooldown', 60),
                'auto_fill' => get_option('otp_login_pro_auto_fill', true),
                'sound_enabled' => get_option('otp_login_pro_sound', false),
            ]
        ]);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only on our admin pages
        if (strpos($hook, 'otp-login-pro') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'otp-login-pro-admin',
            OTP_LOGIN_PRO_ASSETS_URL . 'css/admin.css',
            [],
            OTP_LOGIN_PRO_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'otp-login-pro-admin',
            OTP_LOGIN_PRO_ASSETS_URL . 'js/admin.js',
            ['jquery', 'wp-color-picker', 'jquery-ui-sortable'],
            OTP_LOGIN_PRO_VERSION,
            true
        );
        
        wp_localize_script('otp-login-pro-admin', 'otpLoginProAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('otp_login_pro_admin_nonce'),
            'rest_url' => rest_url('otp-pro/v1/'),
        ]);
    }
    
    /**
     * Get setting
     */
    public function get_setting($key, $default = '') {
        return get_option('otp_login_pro_' . $key, $default);
    }
    
    /**
     * Update setting
     */
    public function update_setting($key, $value) {
        return update_option('otp_login_pro_' . $key, $value);
    }
}

/**
 * Returns the main instance of OTP_Login_Pro
 */
function otp_login_pro() {
    return OTP_Login_Pro::instance();
}

// Initialize
otp_login_pro();
