<?php
/**
 * White Label Options
 * Customize branding for agencies
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_White_Label {
    
    public function __construct() {
        if (!$this->is_white_label_enabled()) {
            return;
        }
        
        add_filter('otp_login_pro_plugin_name', [$this, 'filter_plugin_name']);
        add_filter('otp_login_pro_plugin_description', [$this, 'filter_description']);
        add_filter('otp_login_pro_support_url', [$this, 'filter_support_url']);
        add_filter('admin_footer_text', [$this, 'remove_footer']);
        add_action('admin_head', [$this, 'custom_admin_css']);
        add_action('login_head', [$this, 'custom_login_css']);
    }
    
    /**
     * Check if white label is enabled
     */
    private function is_white_label_enabled() {
        return OTP_Login_Pro_License_Manager::get_tier() === 'agency';
    }
    
    /**
     * Filter plugin name
     */
    public function filter_plugin_name($name) {
        $custom = get_option('otp_white_label_name', '');
        return !empty($custom) ? $custom : $name;
    }
    
    /**
     * Filter description
     */
    public function filter_description($desc) {
        $custom = get_option('otp_white_label_description', '');
        return !empty($custom) ? $custom : $desc;
    }
    
    /**
     * Filter support URL
     */
    public function filter_support_url($url) {
        $custom = get_option('otp_white_label_support_url', '');
        return !empty($custom) ? $custom : $url;
    }
    
    /**
     * Remove footer credits
     */
    public function remove_footer($text) {
        if (isset($_GET['page']) && strpos($_GET['page'], 'otp-login-pro') !== false) {
            $custom = get_option('otp_white_label_footer', '');
            return !empty($custom) ? $custom : '';
        }
        return $text;
    }
    
    /**
     * Custom admin CSS
     */
    public function custom_admin_css() {
        $logo = get_option('otp_white_label_logo', '');
        $primary_color = get_option('otp_white_label_primary_color', '#667eea');
        $secondary_color = get_option('otp_white_label_secondary_color', '#764ba2');
        
        ?>
        <style>
            <?php if ($logo): ?>
            #adminmenu .toplevel_page_otp-login-pro .wp-menu-image:before {
                content: '';
                background: url(<?php echo esc_url($logo); ?>) no-repeat center;
                background-size: 20px 20px;
            }
            <?php endif; ?>
            
            .otp-login-pro .button-primary,
            .otp-gateway-card.active {
                background: <?php echo esc_attr($primary_color); ?> !important;
                border-color: <?php echo esc_attr($primary_color); ?> !important;
            }
            
            .otp-stat-card {
                border-top-color: <?php echo esc_attr($secondary_color); ?> !important;
            }
        </style>
        <?php
    }
    
    /**
     * Custom login CSS
     */
    public function custom_login_css() {
        $logo = get_option('otp_white_label_login_logo', '');
        $primary_color = get_option('otp_white_label_primary_color', '#667eea');
        
        if ($logo || $primary_color !== '#667eea') {
            ?>
            <style>
                <?php if ($logo): ?>
                .otp-login-form h2:before {
                    content: '';
                    display: block;
                    width: 100px;
                    height: 100px;
                    background: url(<?php echo esc_url($logo); ?>) no-repeat center;
                    background-size: contain;
                    margin: 0 auto 20px;
                }
                <?php endif; ?>
                
                .otp-button {
                    background: <?php echo esc_attr($primary_color); ?> !important;
                    border-color: <?php echo esc_attr($primary_color); ?> !important;
                }
            </style>
            <?php
        }
    }
    
    /**
     * White label settings page
     */
    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_POST['otp_white_label_nonce'])) {
            check_admin_referer('otp_white_label_settings', 'otp_white_label_nonce');
            self::save_settings();
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('White Label Settings', 'otp-login-pro'); ?></h1>
            
            <?php if (OTP_Login_Pro_License_Manager::get_tier() !== 'agency'): ?>
                <div class="notice notice-warning">
                    <p><?php _e('White label features require an Agency license.', 'otp-login-pro'); ?></p>
                </div>
            <?php else: ?>
                
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('otp_white_label_settings', 'otp_white_label_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Plugin Name', 'otp-login-pro'); ?></th>
                            <td>
                                <input type="text" name="otp_white_label_name" 
                                       value="<?php echo esc_attr(get_option('otp_white_label_name', '')); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('Custom name shown in admin', 'otp-login-pro'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><?php _e('Description', 'otp-login-pro'); ?></th>
                            <td>
                                <textarea name="otp_white_label_description" rows="3" class="large-text"><?php 
                                    echo esc_textarea(get_option('otp_white_label_description', ''));
                                ?></textarea>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><?php _e('Support URL', 'otp-login-pro'); ?></th>
                            <td>
                                <input type="url" name="otp_white_label_support_url" 
                                       value="<?php echo esc_attr(get_option('otp_white_label_support_url', '')); ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th><?php _e('Admin Logo URL', 'otp-login-pro'); ?></th>
                            <td>
                                <input type="url" name="otp_white_label_logo" 
                                       value="<?php echo esc_attr(get_option('otp_white_label_logo', '')); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('20x20 icon for admin menu', 'otp-login-pro'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><?php _e('Login Logo URL', 'otp-login-pro'); ?></th>
                            <td>
                                <input type="url" name="otp_white_label_login_logo" 
                                       value="<?php echo esc_attr(get_option('otp_white_label_login_logo', '')); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('Logo shown on login form', 'otp-login-pro'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><?php _e('Primary Color', 'otp-login-pro'); ?></th>
                            <td>
                                <input type="color" name="otp_white_label_primary_color" 
                                       value="<?php echo esc_attr(get_option('otp_white_label_primary_color', '#667eea')); ?>" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th><?php _e('Secondary Color', 'otp-login-pro'); ?></th>
                            <td>
                                <input type="color" name="otp_white_label_secondary_color" 
                                       value="<?php echo esc_attr(get_option('otp_white_label_secondary_color', '#764ba2')); ?>" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th><?php _e('Footer Text', 'otp-login-pro'); ?></th>
                            <td>
                                <input type="text" name="otp_white_label_footer" 
                                       value="<?php echo esc_attr(get_option('otp_white_label_footer', '')); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('Replace "Thank you for creating with WordPress" text', 'otp-login-pro'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Save White Label Settings', 'otp-login-pro')); ?>
                </form>
                
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
    private static function save_settings() {
        update_option('otp_white_label_name', sanitize_text_field($_POST['otp_white_label_name'] ?? ''));
        update_option('otp_white_label_description', sanitize_textarea_field($_POST['otp_white_label_description'] ?? ''));
        update_option('otp_white_label_support_url', esc_url_raw($_POST['otp_white_label_support_url'] ?? ''));
        update_option('otp_white_label_logo', esc_url_raw($_POST['otp_white_label_logo'] ?? ''));
        update_option('otp_white_label_login_logo', esc_url_raw($_POST['otp_white_label_login_logo'] ?? ''));
        update_option('otp_white_label_primary_color', sanitize_hex_color($_POST['otp_white_label_primary_color'] ?? '#667eea'));
        update_option('otp_white_label_secondary_color', sanitize_hex_color($_POST['otp_white_label_secondary_color'] ?? '#764ba2'));
        update_option('otp_white_label_footer', sanitize_text_field($_POST['otp_white_label_footer'] ?? ''));
        
        add_settings_error('otp_white_label_settings', 'settings_updated', __('White label settings saved', 'otp-login-pro'), 'success');
    }
}

new OTP_Login_Pro_White_Label();
