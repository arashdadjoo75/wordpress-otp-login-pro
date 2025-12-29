<?php
/**
 * License Manager
 * Handles plugin licensing and activation
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_License_Manager {
    
    private $api_url = 'https://yourdomain.com/api/licenses/';
    
    public function __construct() {
        add_action('admin_init', [$this, 'check_license']);
        add_action('admin_notices', [$this, 'license_notices']);
    }
    
    /**
     * Activate license
     */
    public function activate_license($license_key) {
        $response = wp_remote_post($this->api_url . 'activate', [
            'body' => [
                'license_key' => $license_key,
                'domain' => home_url(),
                'plugin_version' => OTP_LOGIN_PRO_VERSION,
            ],
        ]);
        
        if (is_wp_error($response)) {
            return ['success' => false, 'message' => $response->get_error_message()];
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['success']) && $data['success']) {
            update_option('otp_login_pro_license_key', $license_key);
            update_option('otp_login_pro_license_status', 'active');
            update_option('otp_login_pro_license_expires', $data['expires']);
            update_option('otp_login_pro_license_tier', $data['tier']);
            
            return ['success' => true, 'message' => __('License activated successfully', 'otp-login-pro')];
        }
        
        return ['success' => false, 'message' => $data['message'] ?? __('License activation failed', 'otp-login-pro')];
    }
    
    /**
     * Deactivate license
     */
    public function deactivate_license() {
        $license_key = get_option('otp_login_pro_license_key');
        
        wp_remote_post($this->api_url . 'deactivate', [
            'body' => [
                'license_key' => $license_key,
                'domain' => home_url(),
            ],
        ]);
        
        delete_option('otp_login_pro_license_key');
        delete_option('otp_login_pro_license_status');
        delete_option('otp_login_pro_license_expires');
        delete_option('otp_login_pro_license_tier');
        
        return ['success' => true, 'message' => __('License deactivated', 'otp-login-pro')];
    }
    
    /**
     * Check license status
     */
    public function check_license() {
        $last_check = get_option('otp_login_pro_license_last_check', 0);
        
        // Check once per day
        if (time() - $last_check < DAY_IN_SECONDS) {
            return;
        }
        
        $license_key = get_option('otp_login_pro_license_key');
        
        if (empty($license_key)) {
            return;
        }
        
        $response = wp_remote_get($this->api_url . 'check?' . http_build_query([
            'license_key' => $license_key,
            'domain' => home_url(),
        ]));
        
        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            
            if (isset($data['status'])) {
                update_option('otp_login_pro_license_status', $data['status']);
                update_option('otp_login_pro_license_expires', $data['expires'] ?? '');
            }
        }
        
        update_option('otp_login_pro_license_last_check', time());
    }
    
    /**
     * Check if license is valid
     */
    public static function is_valid() {
        $status = get_option('otp_login_pro_license_status');
        $expires = get_option('otp_login_pro_license_expires');
        
        if ($status !== 'active') {
            return false;
        }
        
        if ($expires && strtotime($expires) < time()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get license tier
     */
    public static function get_tier() {
        return get_option('otp_login_pro_license_tier', 'free');
    }
    
    /**
     * Check if feature is available
     */
    public static function has_feature($feature) {
        $tier = self::get_tier();
        
        $features = [
            'free' => ['basic_otp', 'sms', 'email'],
            'pro' => ['basic_otp', 'sms', 'email', 'whatsapp', '2fa', 'analytics', 'integrations'],
            'agency' => ['all'],
        ];
        
        if ($tier === 'agency' || in_array('all', $features[$tier] ?? [])) {
            return true;
        }
        
        return in_array($feature, $features[$tier] ?? []);
    }
    
    /**
     * Admin notices
     */
    public function license_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $status = get_option('otp_login_pro_license_status');
        $expires = get_option('otp_login_pro_license_expires');
        
        if ($status !== 'active') {
            ?>
            <div class="notice notice-warning">
                <p><?php _e('OTP Login Pro: Please activate your license to receive updates and support.', 'otp-login-pro'); ?>
                   <a href="<?php echo admin_url('admin.php?page=otp-login-pro-settings#license'); ?>"><?php _e('Activate Now', 'otp-login-pro'); ?></a>
                </p>
            </div>
            <?php
        } elseif ($expires && strtotime($expires) - time() < (7 * DAY_IN_SECONDS)) {
            ?>
            <div class="notice notice-info">
                <p><?php _e('OTP Login Pro: Your license will expire soon. Please renew to continue receiving updates.', 'otp-login-pro'); ?></p>
            </div>
            <?php
        }
    }
}

new OTP_Login_Pro_License_Manager();
