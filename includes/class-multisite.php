<?php
/**
 * Multi-Site Network Support
 * WordPress Multisite integration
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Multisite {
    
    public function __construct() {
        if (!is_multisite()) {
            return;
        }
        
        add_action('network_admin_menu', [$this, 'add_network_menu']);
        add_action('wpmu_new_blog', [$this, 'activate_on_new_blog'], 10, 6);
        add_filter('wpmu_drop_tables', [$this, 'drop_tables_on_blog_delete']);
    }
    
    /**
     * Add network admin menu
     */
    public function add_network_menu() {
        add_menu_page(
            __('OTP Network Settings', 'otp-login-pro'),
            __('OTP Network', 'otp-login-pro'),
            'manage_network_options',
            'otp-network-settings',
            [$this, 'render_network_settings'],
            'dashicons-shield-alt',
            30
        );
    }
    
    /**
     * Render network settings page
     */
    public function render_network_settings() {
        if (isset($_POST['otp_network_settings_nonce'])) {
            check_admin_referer('otp_network_settings', 'otp_network_settings_nonce');
            $this->save_network_settings();
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('OTP Login Pro - Network Settings', 'otp-login-pro'); ?></h1>
            
            <form method="post">
                <?php wp_nonce_field('otp_network_settings', 'otp_network_settings_nonce'); ?>
                
                <h2><?php _e('Network-Wide Settings', 'otp-login-pro'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('Enable for All Sites', 'otp-login-pro'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="otp_network_enabled" value="1" 
                                       <?php checked(get_site_option('otp_network_enabled', false)); ?> />
                                <?php _e('Enable OTP Login for all sites in network', 'otp-login-pro'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('Force Network Settings', 'otp-login-pro'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="otp_force_network_settings" value="1"
                                       <?php checked(get_site_option('otp_force_network_settings', false)); ?> />
                                <?php _e('Prevent individual sites from changing settings', 'otp-login-pro'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('Shared Gateway', 'otp-login-pro'); ?></th>
                        <td>
                            <select name="otp_network_gateway">
                                <option value=""><?php _e('Allow sites to choose', 'otp-login-pro'); ?></option>
                                <?php
                                $gateways = OTP_Login_Pro_Gateway_Adapter::get_all_gateways();
                                $current = get_site_option('otp_network_gateway', '');
                                foreach ($gateways as $gateway):
                                ?>
                                    <option value="<?php echo esc_attr($gateway['id']); ?>" <?php selected($current, $gateway['id']); ?>>
                                        <?php echo esc_html($gateway['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Use same gateway for all sites', 'otp-login-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('Centralized Logging', 'otp-login-pro'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="otp_centralized_logging" value="1"
                                       <?php checked(get_site_option('otp_centralized_logging', false)); ?> />
                                <?php _e('Store all logs in main site database', 'otp-login-pro'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Per-Site Configuration', 'otp-login-pro'); ?></h2>
                
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Site', 'otp-login-pro'); ?></th>
                            <th><?php _e('Status', 'otp-login-pro'); ?></th>
                            <th><?php _e('Total OTPs', 'otp-login-pro'); ?></th>
                            <th><?php _e('Actions', 'otp-login-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sites = get_sites();
                        foreach ($sites as $site):
                            switch_to_blog($site->blog_id);
                            $enabled = get_option('otp_login_pro_enabled', false);
                            $count = $this->get_site_otp_count($site->blog_id);
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($site->blogname); ?></strong><br>
                                    <small><?php echo esc_url($site->siteurl); ?></small>
                                </td>
                                <td>
                                    <?php if ($enabled): ?>
                                        <span style="color:green;">● Active</span>
                                    <?php else: ?>
                                        <span style="color:gray;">○ Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($count); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=otp-login-pro'); ?>" class="button">
                                        <?php _e('Configure', 'otp-login-pro'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php
                            restore_current_blog();
                        endforeach;
                        ?>
                    </tbody>
                </table>
                
                <?php submit_button(__('Save Network Settings', 'otp-login-pro')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Save network settings
     */
    private function save_network_settings() {
        update_site_option('otp_network_enabled', isset($_POST['otp_network_enabled']));
        update_site_option('otp_force_network_settings', isset($_POST['otp_force_network_settings']));
        update_site_option('otp_network_gateway', sanitize_text_field($_POST['otp_network_gateway'] ?? ''));
        update_site_option('otp_centralized_logging', isset($_POST['otp_centralized_logging']));
        
        add_settings_error('otp_network_settings', 'settings_updated', __('Network settings saved', 'otp-login-pro'), 'success');
    }
    
    /**
     * Get OTP count for site
     */
    private function get_site_otp_count($blog_id) {
        global $wpdb;
        
        switch_to_blog($blog_id);
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}otp_logs");
        restore_current_blog();
        
        return $count;
    }
    
    /**
     * Activate on new blog
     */
    public function activate_on_new_blog($blog_id) {
        if (get_site_option('otp_network_enabled', false)) {
            switch_to_blog($blog_id);
            OTP_Login_Pro_Installer::activate();
            restore_current_blog();
        }
    }
    
    /**
     * Drop tables on blog delete
     */
    public function drop_tables_on_blog_delete($tables) {
        global $wpdb;
        
        $otp_tables = [
            $wpdb->prefix . 'otp_logs',
            $wpdb->prefix . 'otp_sessions',
            $wpdb->prefix . 'otp_rate_limits',
            $wpdb->prefix . 'otp_trusted_devices',
            $wpdb->prefix . 'otp_backup_codes',
            $wpdb->prefix . 'otp_analytics',
            $wpdb->prefix . 'otp_phone_numbers',
            $wpdb->prefix . 'otp_settings',
            $wpdb->prefix . 'otp_credits',
            $wpdb->prefix . 'otp_transactions',
        ];
        
        return array_merge($tables, $otp_tables);
    }
}

new OTP_Login_Pro_Multisite();
