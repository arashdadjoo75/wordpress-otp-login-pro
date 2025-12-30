<?php
/**
 * Admin Interface - Complete Settings and Dashboard
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Admin {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_otp_pro_test_provider', [$this, 'ajax_test_provider']);
        add_action('wp_ajax_otp_pro_get_stats', [$this, 'ajax_get_stats']);
    }
    
    public function add_menu_pages() {
        add_menu_page(
            __('OTP Login Pro', 'otp-login-pro'),
            __('OTP Login Pro', 'otp-login-pro'),
            'manage_options',
            'otp-login-pro',
            [$this, 'dashboard_page'],
            'dashicons-smartphone',
            30
        );
        
        add_submenu_page('otp-login-pro', __('Dashboard', 'otp-login-pro'), __('Dashboard', 'otp-login-pro'), 'manage_options', 'otp-login-pro', [$this, 'dashboard_page']);
        add_submenu_page('otp-login-pro', __('Settings', 'otp-login-pro'), __('Settings', 'otp-login-pro'), 'manage_options', 'otp-login-pro-settings', [$this, 'settings_page']);
        add_submenu_page('otp-login-pro', __('Providers', 'otp-login-pro'), __('Providers', 'otp-login-pro'), 'manage_options', 'otp-login-pro-providers', [$this, 'providers_page']);
        add_submenu_page('otp-login-pro', __('Analytics', 'otp-login-pro'), __('Analytics', 'otp-login-pro'), 'manage_options', 'otp-login-pro-analytics', [$this, 'analytics_page']);
        add_submenu_page('otp-login-pro', __('Logs', 'otp-login-pro'), __('Logs', 'otp-login-pro'), 'manage_options', 'otp-login-pro-logs', [$this, 'logs_page']);
        add_submenu_page('otp-login-pro', __('Integrations', 'otp-login-pro'), __('Integrations', 'otp-login-pro'), 'manage_options', 'otp-login-pro-integrations', [$this, 'integrations_page']);
    }
    
    public function register_settings() {
        register_setting('otp_login_pro_settings', 'otp_login_pro_enabled');
        register_setting('otp_login_pro_settings', 'otp_login_pro_method');
        register_setting('otp_login_pro_settings', 'otp_login_pro_otp_length');
        register_setting('otp_login_pro_settings', 'otp_login_pro_expiry');
        register_setting('otp_login_pro_settings', 'otp_login_pro_cooldown');
        register_setting('otp_login_pro_settings', 'otp_login_pro_theme');
        register_setting('otp_login_pro_settings', 'otp_login_pro_registration_enabled');
        // ... more settings
    }
    
    public function dashboard_page() {
        $analytics = new OTP_Login_Pro_Analytics();
        $stats = $analytics->get_stats('30days');
        $daily = $analytics->get_daily_stats(7);
        
        ?>
        <div class="wrap">
            <h1><?php _e('OTP Login Pro Dashboard', 'otp-login-pro'); ?></h1>
            
            <div class="otp-dashboard-stats">
                <div class="otp-stat-card">
                    <h3><?php _e('Total OTP Sent', 'otp-login-pro'); ?></h3>
                    <p class="otp-stat-number"><?php echo number_format($stats['total_sent']); ?></p>
                </div>
                
                <div class="otp-stat-card">
                    <h3><?php _e('Success Rate', 'otp-login-pro'); ?></h3>
                    <p class="otp-stat-number"><?php echo $stats['success_rate']; ?>%</p>
                </div>
                
                <div class="otp-stat-card">
                    <h3><?php _e('Verified Logins', 'otp-login-pro'); ?></h3>
                    <p class="otp-stat-number"><?php echo number_format($stats['total_verified']); ?></p>
                </div>
                
                <div class="otp-stat-card">
                    <h3><?php _e('Total Cost', 'otp-login-pro'); ?></h3>
                    <p class="otp-stat-number">$<?php echo number_format($stats['total_cost'], 2); ?></p>
                </div>
            </div>
            
            <div class="otp-chart-container">
                <h2><?php _e('Last 7 Days Activity', 'otp-login-pro'); ?></h2>
                <canvas id="otp-chart" width="800" height="300"></canvas>
            </div>
            
            <script>
            // Chart.js implementation would go here
            </script>
        </div>
        
        <style>
            .otp-dashboard-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin:20px 0}
            .otp-stat-card{background:#fff;padding:20px;border-radius:8px;border-left:4px solid #667eea;box-shadow:0 2px 8px rgba(0,0,0,.1)}
            .otp-stat-card h3{margin:0 0 10px;color:#666;font-size:14px}
            .otp-stat-number{font-size:32px;font-weight:bold;color:#333;margin:0}
            .otp-chart-container{background:#fff;padding:20px;border-radius:8px;margin-top:20px}
        </style>
        <?php
    }
    
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('OTP Login Pro Settings', 'otp-login-pro'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('otp_login_pro_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('Enable OTP Login', 'otp-login-pro'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="otp_login_pro_enabled" value="1" <?php checked(get_option('otp_login_pro_enabled'), '1'); ?> />
                                <?php _e('Enable OTP authentication system', 'otp-login-pro'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('Authentication Method', 'otp-login-pro'); ?></th>
                        <td>
                            <select name="otp_login_pro_method">
                                <option value="sms" <?php selected(get_option('otp_login_pro_method'), 'sms'); ?>>SMS Only</option>
                                <option value="email" <?php selected(get_option('otp_login_pro_method'), 'email'); ?>>Email Only</option>
                                <option value="both" <?php selected(get_option('otp_login_pro_method'), 'both'); ?>>Both (Auto-detect)</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('OTP Length', 'otp-login-pro'); ?></th>
                        <td>
                            <input type="number" name="otp_login_pro_otp_length" value="<?php echo esc_attr(get_option('otp_login_pro_otp_length', '6')); ?>" min="4" max="10" />
                            <p class="description"><?php _e('Number of digits in OTP code (4-10)', 'otp-login-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('OTP Expiry (seconds)', 'otp-login-pro'); ?></th>
                        <td>
                            <input type="number" name="otp_login_pro_expiry" value="<?php echo esc_attr(get_option('otp_login_pro_expiry', '300')); ?>" min="60" max="3600" />
                            <p class="description"><?php _e('How long the OTP code remains valid (default: 300 seconds = 5 minutes)', 'otp-login-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('Cooldown Period (seconds)', 'otp-login-pro'); ?></th>
                        <td>
                            <input type="number" name="otp_login_pro_cooldown" value="<?php echo esc_attr(get_option('otp_login_pro_cooldown', '60')); ?>" min="0" max="300" />
                            <p class="description"><?php _e('Wait time between OTP requests', 'otp-login-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('UI Theme', 'otp-login-pro'); ?></th>
                        <td>
                            <select name="otp_login_pro_theme">
                                <option value="modern" <?php selected(get_option('otp_login_pro_theme'), 'modern'); ?>>Modern</option>
                                <option value="minimal" <?php selected(get_option('otp_login_pro_theme'), 'minimal'); ?>>Minimal</option>
                                <option value="corporate" <?php selected(get_option('otp_login_pro_theme'), 'corporate'); ?>>Corporate</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('Enable Registration', 'otp-login-pro'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="otp_login_pro_registration_enabled" value="1" <?php checked(get_option('otp_login_pro_registration_enabled'), '1'); ?> />
                                <?php _e('Allow new users to register via OTP', 'otp-login-pro'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function providers_page() {
        // Ensure gateway adapter is loaded
        if (!class_exists('OTP_Login_Pro_Gateway_Adapter')) {
            $gateway_adapter_file = OTP_LOGIN_PRO_INCLUDES . 'providers/class-gateway-adapter.php';
            if (file_exists($gateway_adapter_file)) {
                require_once $gateway_adapter_file;
            }
        }
        
        // Ensure Provider Manager is loaded
        if (!class_exists('OTP_Login_Pro_Provider_Manager')) {
            $provider_manager_file = OTP_LOGIN_PRO_INCLUDES . 'providers/class-provider-manager.php';
            if (file_exists($provider_manager_file)) {
                require_once $provider_manager_file;
            }
        }
        
        // Load Provider Manager to get international providers
        $international_providers = [];
        if (class_exists('OTP_Login_Pro_Provider_Manager')) {
            $provider_manager = new OTP_Login_Pro_Provider_Manager();
            $international_providers = $provider_manager->get_all_providers();
        }
        
        // Filter SMS providers only
        $sms_providers = [];
        foreach ($international_providers as $provider_id => $provider_info) {
            if ($provider_info['type'] === 'sms') {
                // Create temporary instance to get name and cost
                if (class_exists($provider_info['class'])) {
                    $temp_instance = new $provider_info['class']([]);
                    $sms_providers[$provider_id] = [
                        'id' => $provider_id,
                        'name' => $temp_instance->get_name(),
                        'cost' => $temp_instance->get_cost(),
                        'class' => $provider_info['class'],
                        'type' => 'international'
                    ];
                }
            }
        }
        
        // Load gateways if gateway adapter exists
        $gateways = [];
        if (class_exists('OTP_Login_Pro_Gateway_Adapter')) {
            $gateways = OTP_Login_Pro_Gateway_Adapter::get_all_gateways();
            // Convert gateways format to match providers format
            foreach ($gateways as $gateway_id => $gateway) {
                $gateways[$gateway_id]['type'] = 'gateway';
            }
        }
        
        $active_gateway = get_option('otp_login_pro_active_gateway', '');
        $active_sms_provider = get_option('otp_login_pro_sms_provider', '');
        
        // Helper function to check if international provider is configured
        $is_provider_configured = function($provider_id) {
            $config = get_option("otp_login_pro_provider_{$provider_id}", []);
            return !empty($config) && isset($config['enabled']) && $config['enabled'];
        };
        
        // Helper function to check if gateway is configured
        $is_gateway_configured = function($gateway_id) {
            $username = get_option("otp_login_pro_gateway_{$gateway_id}_username", '');
            $password = get_option("otp_login_pro_gateway_{$gateway_id}_password", '');
            $sender = get_option("otp_login_pro_gateway_{$gateway_id}_sender", '');
            return !empty($username) || !empty($password);
        };
        
        ?>
        <div class="wrap">
            <h1><?php _e('OTP Providers Configuration', 'otp-login-pro'); ?></h1>
            
            <h2><?php _e('International SMS Providers', 'otp-login-pro'); ?></h2>
            <?php if (empty($sms_providers)): ?>
                <div class="notice notice-warning">
                    <p><?php _e('No international SMS providers found.', 'otp-login-pro'); ?></p>
                </div>
            <?php else: ?>
                <p class="description"><?php printf(__('Found %d international SMS provider(s).', 'otp-login-pro'), count($sms_providers)); ?></p>
                
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Provider', 'otp-login-pro'); ?></th>
                            <th><?php _e('Provider ID', 'otp-login-pro'); ?></th>
                            <th><?php _e('Cost/SMS', 'otp-login-pro'); ?></th>
                            <th><?php _e('Status', 'otp-login-pro'); ?></th>
                            <th><?php _e('Actions', 'otp-login-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sms_providers as $provider_id => $provider): ?>
                            <?php 
                            $is_configured = $is_provider_configured($provider_id);
                            $is_active = ($provider_id === $active_sms_provider);
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($provider['name']); ?></strong></td>
                                <td><code><?php echo esc_html($provider_id); ?></code></td>
                                <td>$<?php echo number_format($provider['cost'], 4); ?></td>
                                <td>
                                    <?php if ($is_active && $is_configured): ?>
                                        <span class="dashicons dashicons-yes-alt" style="color:green"></span> 
                                        <strong><?php _e('Active & Configured', 'otp-login-pro'); ?></strong>
                                    <?php elseif ($is_configured): ?>
                                        <span class="dashicons dashicons-yes-alt" style="color:blue"></span> 
                                        <?php _e('Configured', 'otp-login-pro'); ?>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-minus" style="color:#ccc"></span> 
                                        <?php _e('Not Configured', 'otp-login-pro'); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=otp-login-pro-settings')); ?>" class="button button-small">
                                        <?php _e('Configure', 'otp-login-pro'); ?>
                                    </a>
                                    <?php if ($is_configured): ?>
                                        <button type="button" class="button button-small otp-test-provider" data-provider="<?php echo esc_attr($provider_id); ?>">
                                            <?php _e('Test', 'otp-login-pro'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th><?php _e('Provider', 'otp-login-pro'); ?></th>
                            <th><?php _e('Provider ID', 'otp-login-pro'); ?></th>
                            <th><?php _e('Cost/SMS', 'otp-login-pro'); ?></th>
                            <th><?php _e('Status', 'otp-login-pro'); ?></th>
                            <th><?php _e('Actions', 'otp-login-pro'); ?></th>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>
            
            <h2 class="mt-4" style="margin-top: 30px;"><?php _e('Gateway-based SMS Providers', 'otp-login-pro'); ?></h2>
            <?php if (empty($gateways)): ?>
                <div class="notice notice-warning">
                    <p><?php _e('No SMS gateways found. Please ensure gateway files are present in the gateways directory.', 'otp-login-pro'); ?></p>
                </div>
            <?php else: ?>
                <p class="description"><?php printf(__('Found %d SMS gateway(s). Configure them in <a href="%s">SMS Gateways</a> page.', 'otp-login-pro'), count($gateways), admin_url('admin.php?page=otp-login-pro-gateways')); ?></p>
                
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Provider', 'otp-login-pro'); ?></th>
                            <th><?php _e('Gateway ID', 'otp-login-pro'); ?></th>
                            <th><?php _e('Status', 'otp-login-pro'); ?></th>
                            <th><?php _e('Actions', 'otp-login-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gateways as $gateway_id => $gateway): ?>
                            <?php 
                            $is_configured = $is_gateway_configured($gateway_id);
                            $is_active = ($gateway_id === $active_gateway);
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($gateway['name']); ?></strong></td>
                                <td><code><?php echo esc_html($gateway_id); ?></code></td>
                                <td>
                                    <?php if ($is_active && $is_configured): ?>
                                        <span class="dashicons dashicons-yes-alt" style="color:green"></span> 
                                        <strong><?php _e('Active & Configured', 'otp-login-pro'); ?></strong>
                                    <?php elseif ($is_configured): ?>
                                        <span class="dashicons dashicons-yes-alt" style="color:blue"></span> 
                                        <?php _e('Configured', 'otp-login-pro'); ?>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-minus" style="color:#ccc"></span> 
                                        <?php _e('Not Configured', 'otp-login-pro'); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=otp-login-pro-gateways')); ?>" class="button button-small">
                                        <?php _e('Configure', 'otp-login-pro'); ?>
                                    </a>
                                    <?php if ($is_configured): ?>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=otp-login-pro-gateways')); ?>#test" class="button button-small">
                                            <?php _e('Test', 'otp-login-pro'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th><?php _e('Provider', 'otp-login-pro'); ?></th>
                            <th><?php _e('Gateway ID', 'otp-login-pro'); ?></th>
                            <th><?php _e('Status', 'otp-login-pro'); ?></th>
                            <th><?php _e('Actions', 'otp-login-pro'); ?></th>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>
            
            <h2 class="mt-4" style="margin-top: 30px;"><?php _e('Email Providers', 'otp-login-pro'); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Provider', 'otp-login-pro'); ?></th>
                        <th><?php _e('Status', 'otp-login-pro'); ?></th>
                        <th><?php _e('Actions', 'otp-login-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>WordPress Mail</td>
                        <td><span class="dashicons dashicons-yes-alt" style="color:green"></span> Active</td>
                        <td><a href="#wp-mail-config" class="button">Configure</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function analytics_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Analytics & Reports', 'otp-login-pro'); ?></h1>
            <p><?php _e('Detailed analytics and reports will be displayed here', 'otp-login-pro'); ?></p>
        </div>
        <?php
    }
    
    public function logs_page() {
        global $wpdb;
        
        $logs = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}otp_logs 
            ORDER BY created_at DESC 
            LIMIT 100
        ");
        
        ?>
        <div class="wrap">
            <h1><?php _e('OTP Logs', 'otp-login-pro'); ?></h1>
            
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'otp-login-pro'); ?></th>
                        <th><?php _e('Identifier', 'otp-login-pro'); ?></th>
                        <th><?php _e('Method', 'otp-login-pro'); ?></th>
                        <th><?php _e('Provider', 'otp-login-pro'); ?></th>
                        <th><?php _e('Status', 'otp-login-pro'); ?></th>
                        <th><?php _e('IP Address', 'otp-login-pro'); ?></th>
                        <th><?php _e('Date', 'otp-login-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo $log->id; ?></td>
                        <td><?php echo esc_html($log->identifier); ?></td>
                        <td><?php echo esc_html($log->method); ?></td>
                        <td><?php echo esc_html($log->provider); ?></td>
                        <td><span class="status-<?php echo $log->status; ?>"><?php echo ucfirst($log->status); ?></span></td>
                        <td><?php echo esc_html($log->ip_address); ?></td>
                        <td><?php echo esc_html($log->created_at); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function integrations_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Integrations', 'otp-login-pro'); ?></h1>
            
            <div class="otp-integrations-grid">
                <div class="integration-card">
                    <h3>WooCommerce</h3>
                    <p><?php _e('Add OTP verification to checkout', 'otp-login-pro'); ?></p>
                    <label>
                        <input type="checkbox" /> <?php _e('Enable', 'otp-login-pro'); ?>
                    </label>
                </div>
                
                <div class="integration-card">
                    <h3>Elementor</h3>
                    <p><?php _e('OTP Login widget for Elementor', 'otp-login-pro'); ?></p>
                    <label>
                        <input type="checkbox" checked /> <?php _e('Enable', 'otp-login-pro'); ?>
                    </label>
                </div>
                
                <div class="integration-card">
                    <h3>BuddyPress</h3>
                    <p><?php _e('Secure BuddyPress logins with OTP', 'otp-login-pro'); ?></p>
                    <label>
                        <input type="checkbox" /> <?php _e('Enable', 'otp-login-pro'); ?>
                    </label>
                </div>
            </div>
        </div>
        
        <style>
            .otp-integrations-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin:20px 0}
            .integration-card{background:#fff;padding:20px;border-radius:8px;border:1px solid #ddd}
            .integration-card h3{margin-top:0}
        </style>
        <?php
    }
    
    public function ajax_test_provider() {
        check_ajax_referer('otp_login_pro_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $provider_id = sanitize_text_field($_POST['provider_id'] ?? '');
        $test_number = sanitize_text_field($_POST['test_number'] ?? '');
        
        $provider_manager = new OTP_Login_Pro_Provider_Manager();
        $result = $provider_manager->test_provider($provider_id, $test_number);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public function ajax_get_stats() {
        check_ajax_referer('otp_login_pro_admin_nonce', 'nonce');
        
        $analytics = new OTP_Login_Pro_Analytics();
        $period = sanitize_text_field($_POST['period'] ?? '7days');
        
        $stats = $analytics->get_stats($period);
        $daily = $analytics->get_daily_stats(7);
        
        wp_send_json_success([
            'stats' => $stats,
            'daily' => $daily,
        ]);
    }
}
