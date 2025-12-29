<?php
/**
 * GeoIP Blocking
 * Block/allow OTP requests by country
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_GeoIP {
    
    public function __construct() {
        add_filter('otp_login_pro_before_send_otp', [$this, 'check_geo_restrictions'], 10, 2);
    }
    
    /**
     * Check geographic restrictions
     */
    public function check_geo_restrictions($allowed, $identifier) {
        if (!get_option('otp_login_pro_geoip_enabled', false)) {
            return $allowed;
        }
        
        $ip = $this->get_client_ip();
        $country = $this->get_country_by_ip($ip);
        
        if (!$country) {
            // If we can't determine country, allow or block based on settings
            return get_option('otp_login_pro_geoip_allow_unknown', true);
        }
        
        $mode = get_option('otp_login_pro_geoip_mode', 'blacklist'); // blacklist or whitelist
        $countries = get_option('otp_login_pro_geoip_countries', []);
        
        if ($mode === 'whitelist') {
            // Only allow listed countries
            return in_array($country, $countries);
        } else {
            // Block listed countries  
            return !in_array($country, $countries);
        }
    }
    
    /**
     * Get country by IP
     */
    private function get_country_by_ip($ip) {
        // Use free GeoIP service
        $cache_key = 'geoip_' . md5($ip);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Try multiple free services
        $country = $this->try_geoip_service_1($ip);
        
        if (!$country) {
            $country = $this->try_geoip_service_2($ip);
        }
        
        if ($country) {
            // Cache for 24 hours
            set_transient($cache_key, $country, DAY_IN_SECONDS);
        }
        
        return $country;
    }
    
    /**
     * Try ip-api.com (free, 45 requests/minute)
     */
    private function try_geoip_service_1($ip) {
        $response = wp_remote_get("http://ip-api.com/json/{$ip}?fields=countryCode", [
            'timeout' => 3,
        ]);
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data['countryCode'] ?? null;
    }
    
    /**
     * Try ipapi.co (free, 1000 requests/day)
     */
    private function try_geoip_service_2($ip) {
        $response = wp_remote_get("https://ipapi.co/{$ip}/country/", [
            'timeout' => 3,
        ]);
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $country = trim(wp_remote_retrieve_body($response));
        
        return strlen($country) === 2 ? $country : null;
    }
    
    /**
     * Get client IP
     */
    private function get_client_ip() {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Handle comma-separated IPs
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get country name from code
     */
    public static function get_country_name($code) {
        $countries = [
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'IR' => 'Iran',
            'TR' => 'Turkey',
            'AE' => 'United Arab Emirates',
            'SA' => 'Saudi Arabia',
            'DE' => 'Germany',
            'FR' => 'France',
            'IN' => 'India',
            'CN' => 'China',
            'JP' => 'Japan',
            'BR' => 'Brazil',
            // Add more as needed
        ];
        
        return $countries[$code] ?? $code;
    }
    
    /**
     * GeoIP settings page
     */
    public static function render_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_POST['otp_geoip_nonce'])) {
            check_admin_referer('otp_geoip_settings', 'otp_geoip_nonce');
            self::save_settings();
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('GeoIP Blocking Settings', 'otp-login-pro'); ?></h1>
            
            <form method="post">
                <?php wp_nonce_field('otp_geoip_settings', 'otp_geoip_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('Enable GeoIP Blocking', 'otp-login-pro'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="otp_geoip_enabled" value="1"
                                       <?php checked(get_option('otp_login_pro_geoip_enabled', false)); ?> />
                                <?php _e('Enable geographic restrictions', 'otp-login-pro'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('Mode', 'otp-login-pro'); ?></th>
                        <td>
                            <select name="otp_geoip_mode">
                                <option value="blacklist" <?php selected(get_option('otp_login_pro_geoip_mode', 'blacklist'), 'blacklist'); ?>>
                                    <?php _e('Blacklist (Block selected countries)', 'otp-login-pro'); ?>
                                </option>
                                <option value="whitelist" <?php selected(get_option('otp_login_pro_geoip_mode', 'blacklist'), 'whitelist'); ?>>
                                    <?php _e('Whitelist (Only allow selected countries)', 'otp-login-pro'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('Countries'); ?></th>
                        <td>
                            <select name="otp_geoip_countries[]" multiple size="10" style="min-width:300px;">
                                <?php
                                $selected = get_option('otp_login_pro_geoip_countries', []);
                                $all_countries = ['US', 'GB', 'CA', 'AU', 'IR', 'TR', 'AE', 'SA', 'DE', 'FR', 'IN', 'CN', 'JP', 'BR'];
                                
                                foreach ($all_countries as $code):
                                ?>
                                    <option value="<?php echo esc_attr($code); ?>" 
                                            <?php selected(in_array($code, $selected)); ?>>
                                        <?php echo esc_html(self::get_country_name($code) . ' (' . $code . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Hold Ctrl/Cmd to select multiple', 'otp-login-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('Allow Unknown Countries', 'otp-login-pro'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="otp_geoip_allow_unknown" value="1"
                                       <?php checked(get_option('otp_login_pro_geoip_allow_unknown', true)); ?> />
                                <?php _e('Allow requests when country cannot be determined', 'otp-login-pro'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Save GeoIP Settings', 'otp-login-pro')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
    private static function save_settings() {
        update_option('otp_login_pro_geoip_enabled', isset($_POST['otp_geoip_enabled']));
        update_option('otp_login_pro_geoip_mode', sanitize_text_field($_POST['otp_geoip_mode'] ?? 'blacklist'));
        update_option('otp_login_pro_geoip_countries', array_map('sanitize_text_field', $_POST['otp_geoip_countries'] ?? []));
        update_option('otp_login_pro_geoip_allow_unknown', isset($_POST['otp_geoip_allow_unknown']));
        
        add_settings_error('otp_geoip_settings', 'settings_updated', __('GeoIP settings saved', 'otp-login-pro'), 'success');
    }
}

new OTP_Login_Pro_GeoIP();
