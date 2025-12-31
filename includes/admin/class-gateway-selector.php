<?php
/**
 * Gateway Selector Admin Page
 * Allows admin to choose and configure from 118+ SMS gateways and international providers
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Gateway_Selector {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page'], 11);
        add_action('admin_init', [$this, 'save_settings']);
        add_action('wp_ajax_otp_pro_test_gateway', [$this, 'ajax_test_gateway']);
    }
    
    public function add_menu_page() {
        add_submenu_page(
            'otp-login-pro',
            __('SMS Gateways', 'otp-login-pro'),
            __('SMS Gateways', 'otp-login-pro'),
            'manage_options',
            'otp-login-pro-gateways',
            [$this, 'render_page']
        );
    }
    
    /**
     * Get all available gateways (both international and gateway-based)
     */
    private function get_all_gateways() {
        $all_gateways = [];
        
        // Ensure Provider Manager is loaded
        if (!class_exists('OTP_Login_Pro_Provider_Manager')) {
            $provider_manager_file = OTP_LOGIN_PRO_INCLUDES . 'providers/class-provider-manager.php';
            if (file_exists($provider_manager_file)) {
                require_once $provider_manager_file;
            }
        }
        
        // Load international providers
        if (class_exists('OTP_Login_Pro_Provider_Manager')) {
            $provider_manager = new OTP_Login_Pro_Provider_Manager();
            $international_providers = $provider_manager->get_all_providers();
            
            foreach ($international_providers as $provider_id => $provider_info) {
                if ($provider_info['type'] === 'sms' && class_exists($provider_info['class'])) {
                    $temp_instance = new $provider_info['class']([]);
                    $all_gateways[$provider_id] = [
                        'id' => $provider_id,
                        'name' => $temp_instance->get_name(),
                        'type' => 'international',
                        'class' => $provider_info['class'],
                    ];
                }
            }
        }
        
        // Load gateway-based providers
        if (class_exists('OTP_Login_Pro_Gateway_Adapter')) {
            $gateways = OTP_Login_Pro_Gateway_Adapter::get_all_gateways();
            foreach ($gateways as $gateway_id => $gateway) {
                $all_gateways[$gateway_id] = [
                    'id' => $gateway_id,
                    'name' => $gateway['name'],
                    'type' => 'gateway',
                    'class' => $gateway['class'] ?? null,
                ];
            }
        }
        
        return $all_gateways;
    }
    
    /**
     * Get gateway configuration (unified interface)
     */
    private function get_gateway_config($gateway_id, $gateway_type) {
        if ($gateway_type === 'international') {
            // For international providers, map from provider config to unified format
            $config = get_option("otp_login_pro_provider_{$gateway_id}", []);
            
            // Map provider-specific fields to unified fields
            $username = '';
            $password = '';
            $sender = '';
            $templateid = '';
            
            if ($gateway_id === 'twilio') {
                $username = $config['account_sid'] ?? '';
                $password = $config['auth_token'] ?? '';
                $sender = $config['from_number'] ?? '';
            } elseif ($gateway_id === 'vonage') {
                $username = $config['api_key'] ?? '';
                $password = $config['api_secret'] ?? '';
                $sender = $config['from'] ?? '';
            } elseif ($gateway_id === 'kavenegar') {
                $username = $config['api_key'] ?? '';
                $password = '';
                $sender = $config['sender'] ?? '';
                $templateid = $config['templateid']
            } else {
                // Generic mapping
                $username = $config['api_key'] ?? $config['account_sid'] ?? $config['username'] ?? '';
                $password = $config['api_secret'] ?? $config['auth_token'] ?? $config['password'] ?? '';
                $sender = $config['sender'] ?? $config['from'] ?? $config['from_number'] ?? '';
                $templateid = $config['templateid']
            }
            
            return [
                'username' => $username,
                'password' => $password,
                'sender' => $sender,
                'templateid' => $templateid
            ];
        } else {
            // For gateway-based providers, use standard format
            return [
                'username' => get_option("otp_login_pro_gateway_{$gateway_id}_username", ''),
                'password' => get_option("otp_login_pro_gateway_{$gateway_id}_password", ''),
                'sender' => get_option("otp_login_pro_gateway_{$gateway_id}_sender", ''),
                'templateid' => get_option("otp_login_pro_gateway_{$gatemay_id}_templateid")
            ];
        }
    }
    
    /**
     * Save gateway configuration (unified interface)
     */
    private function save_gateway_config($gateway_id, $gateway_type, $username, $password, $sender , $templateid) {
        if ($gateway_type === 'international') {
            // For international providers, map unified fields to provider-specific fields
            $config = get_option("otp_login_pro_provider_{$gateway_id}", []);
            $config['enabled'] = true;
            
            if ($gateway_id === 'twilio') {
                $config['account_sid'] = $username;
                $config['auth_token'] = $password;
                $config['from_number'] = $sender;
            } elseif ($gateway_id === 'vonage') {
                $config['api_key'] = $username;
                $config['api_secret'] = $password;
                $config['from'] = $sender;
            } elseif ($gateway_id === 'kavenegar') {
                $config['api_key'] = $username;
                $config['sender'] = $sender;
                $config['templateid'] = $templateid
            } else {
                // Generic mapping
                $config['api_key'] = $username;
                $config['api_secret'] = $password;
                $config['sender'] = $sender;
                $config['templateid'] = $templateid
            }
            
            update_option("otp_login_pro_provider_{$gateway_id}", $config);
        } else {
            // For gateway-based providers, use standard format
            update_option("otp_login_pro_gateway_{$gateway_id}_username", $username);
            update_option("otp_login_pro_gateway_{$gateway_id}_password", $password);
            update_option("otp_login_pro_gateway_{$gateway_id}_sender", $sender);
            update_option("otp_login_pro_gateway_{$gateway_id}_templateid", $templateid)
        }
    }
    
    public function render_page() {
        $all_gateways = $this->get_all_gateways();
        
        // Get active gateway from URL parameter or option
        $active_gateway_id = isset($_GET['gateway']) ? sanitize_text_field($_GET['gateway']) : get_option('otp_login_pro_active_gateway', '');
        
        // If gateway from URL doesn't exist, use first available
        if (!isset($all_gateways[$active_gateway_id]) && !empty($all_gateways)) {
            $gateway_keys = array_keys($all_gateways);
            $active_gateway_id = $gateway_keys[0];
        }
        
        // Determine gateway type
        $active_gateway_type = 'gateway';
        if (isset($all_gateways[$active_gateway_id])) {
            $active_gateway_type = $all_gateways[$active_gateway_id]['type'];
        }
        
        $config = $this->get_gateway_config($active_gateway_id, $active_gateway_type);
        
        // Separate international and gateway-based
        $international_gateways = array_filter($all_gateways, function($g) { return $g['type'] === 'international'; });
        $persian_gateways = array_filter($all_gateways, function($g) { return $g['type'] === 'gateway'; });
        
        ?>
        <div class="wrap">
            <h1><?php _e('SMS Gateway Configuration', 'otp-login-pro'); ?></h1>
            <p class="description"><?php _e('Choose from international and Persian SMS gateways', 'otp-login-pro'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('otp_gateway_settings', 'otp_gateway_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('Select Gateway', 'otp-login-pro'); ?></th>
                        <td>
                            <select name="otp_active_gateway" id="otp-active-gateway" class="regular-text">
                                <optgroup label="<?php _e('International SMS Gateways', 'otp-login-pro'); ?>">
                                    <?php foreach ($international_gateways as $gateway): ?>
                                        <option value="<?php echo esc_attr($gateway['id']); ?>" 
                                                data-type="international"
                                                <?php selected($active_gateway_id, $gateway['id']); ?>>
                                            <?php echo esc_html($gateway['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="<?php _e('Persian Gateways', 'otp-login-pro'); ?>">
                                    <?php foreach ($persian_gateways as $gateway): ?>
                                        <option value="<?php echo esc_attr($gateway['id']); ?>" 
                                                data-type="gateway"
                                                <?php selected($active_gateway_id, $gateway['id']); ?>>
                                            <?php echo esc_html($gateway['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                            <p class="description">
                                <?php printf(__('%d gateways available', 'otp-login-pro'), count($all_gateways)); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Gateway Settings', 'otp-login-pro'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('API Key / Username', 'otp-login-pro'); ?></th>
                        <td>
                            <input type="text" 
                                   name="otp_gateway_username" 
                                   id="otp_gateway_username"
                                   value="<?php echo esc_attr($config['username']); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('Enter your API key, username, or account SID', 'otp-login-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('API Secret / Password', 'otp-login-pro'); ?></th>
                        <td>
                            <input type="password" 
                                   name="otp_gateway_password" 
                                   id="otp_gateway_password"
                                   value="<?php echo esc_attr($config['password']); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('Enter your API secret, password, or auth token (if required)', 'otp-login-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('Sender Number / From', 'otp-login-pro'); ?></th>
                        <td>
                            <input type="text" 
                                   name="otp_gateway_sender" 
                                   id="otp_gateway_sender"
                                   value="<?php echo esc_attr($config['sender']); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('Your approved sender number or from identifier', 'otp-login-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('Test Gateway', 'otp-login-pro'); ?></th>
                        <td>
                            <input type="tel" id="otp-test-number" placeholder="+989123456789" class="regular-text" />
                            <button type="button" class="button" id="otp-test-gateway-btn">
                                <?php _e('Send Test SMS', 'otp-login-pro'); ?>
                            </button>
                            <div id="otp-test-result" style="margin-top:10px;"></div>
                        </td>
                    </tr>

                    <tr>
                        <th><?php _e('Template ID', 'otp-login-pro'); ?></th>
                        <td>
                            <input type="text" 
                                   name="otp_template_id" 
                                   id="otp_template_id"
                                   value="<?php echo esc_attr($config['templateid']); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e("Enter you're template ID", 'otp-login-pro'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <input type="hidden" name="otp_gateway_type" id="otp_gateway_type" value="<?php echo esc_attr($active_gateway_type); ?>" />
                
                <?php submit_button(__('Save Gateway Settings', 'otp-login-pro')); ?>
            </form>
            
            <hr />
            
            <h2><?php _e('International SMS Gateways', 'otp-login-pro'); ?></h2>
            <?php if (!empty($international_gateways)): ?>
                <div class="otp-gateways-grid">
                    <?php foreach ($international_gateways as $gateway): ?>
                        <div class="otp-gateway-card <?php echo $gateway['id'] === $active_gateway_id ? 'active' : ''; ?>">
                            <h4><?php echo esc_html($gateway['name']); ?></h4>
                            <p><code><?php echo esc_html($gateway['id']); ?></code></p>
                            <?php if ($gateway['id'] === $active_gateway_id): ?>
                                <span class="dashicons dashicons-yes-alt" style="color:green;"></span>
                                <?php _e('Active', 'otp-login-pro'); ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><?php _e('No international gateways found.', 'otp-login-pro'); ?></p>
            <?php endif; ?>
            
            <h2 style="margin-top: 30px;"><?php _e('Persian Gateways', 'otp-login-pro'); ?></h2>
            <?php if (!empty($persian_gateways)): ?>
                <div class="otp-gateways-grid">
                    <?php foreach ($persian_gateways as $gateway): ?>
                        <div class="otp-gateway-card <?php echo $gateway['id'] === $active_gateway_id ? 'active' : ''; ?>">
                            <h4><?php echo esc_html($gateway['name']); ?></h4>
                            <p><code><?php echo esc_html($gateway['id']); ?></code></p>
                            <?php if ($gateway['id'] === $active_gateway_id): ?>
                                <span class="dashicons dashicons-yes-alt" style="color:green;"></span>
                                <?php _e('Active', 'otp-login-pro'); ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><?php _e('No Persian gateways found.', 'otp-login-pro'); ?></p>
            <?php endif; ?>
        </div>
        
        <style>
            .otp-gateways-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:15px;margin-top:20px}
            .otp-gateway-card{background:#fff;padding:15px;border:2px solid #ddd;border-radius:5px;text-align:center}
            .otp-gateway-card.active{border-color:#2271b1;background:#f0f6fc}
            .otp-gateway-card h4{margin:0 0 5px;font-size:14px}
            .otp-gateway-card p{margin:5px 0;font-size:12px;color:#666}
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Update form fields when gateway selection changes
            $('#otp-active-gateway').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var gatewayType = selectedOption.data('type');
                $('#otp_gateway_type').val(gatewayType);
                
                // Reload page to show correct config
                window.location.href = '<?php echo admin_url('admin.php?page=otp-login-pro-gateways'); ?>&gateway=' + encodeURIComponent($(this).val());
            });
            
            $('#otp-test-gateway-btn').on('click', function() {
                var btn = $(this);
                var number = $('#otp-test-number').val();
                var gateway = $('#otp-active-gateway').val();
                var gatewayType = $('#otp_gateway_type').val();
                
                if (!number) {
                    alert('<?php _e('Please enter a test number', 'otp-login-pro'); ?>');
                    return;
                }
                
                btn.prop('disabled', true).text('<?php _e('Sending...', 'otp-login-pro'); ?>');
                
                $.post(ajaxurl, {
                    action: 'otp_pro_test_gateway',
                    nonce: '<?php echo wp_create_nonce('otp_test_gateway'); ?>',
                    gateway: gateway,
                    gateway_type: gatewayType,
                    number: number
                }, function(response) {
                    btn.prop('disabled', false).text('<?php _e('Send Test SMS', 'otp-login-pro'); ?>');
                    
                    if (response.success) {
                        $('#otp-test-result').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    } else {
                        $('#otp-test-result').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function save_settings() {
        if (!isset($_POST['otp_gateway_nonce']) || !wp_verify_nonce($_POST['otp_gateway_nonce'], 'otp_gateway_settings')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $gateway_id = sanitize_text_field($_POST['otp_active_gateway'] ?? '');
        $gateway_type = sanitize_text_field($_POST['otp_gateway_type'] ?? 'gateway');
        $username = sanitize_text_field($_POST['otp_gateway_username'] ?? '');
        $password = sanitize_text_field($_POST['otp_gateway_password'] ?? '');
        $sender = sanitize_text_field($_POST['otp_gateway_sender'] ?? '');
        $templateid = sanitize_text_field($_POST['otp_gateway_templateid'] ?? '');
        
        if ($gateway_id) {
            update_option('otp_login_pro_active_gateway', $gateway_id);
            $this->save_gateway_config($gateway_id, $gateway_type, $username, $password, $sender, $templateid);
            
            add_settings_error('otp_gateway_settings', 'settings_updated', __('Settings saved successfully', 'otp-login-pro'), 'success');
        }
    }
    
    public function ajax_test_gateway() {
        check_ajax_referer('otp_test_gateway', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $gateway_id = sanitize_text_field($_POST['gateway'] ?? '');
        $gateway_type = sanitize_text_field($_POST['gateway_type'] ?? 'gateway');
        $number = sanitize_text_field($_POST['number'] ?? '');
        
        if ($gateway_type === 'international') {
            // Test international provider
            if (!class_exists('OTP_Login_Pro_Provider_Manager')) {
                wp_send_json_error(['message' => 'Provider Manager not available']);
            }
            
            $provider_manager = new OTP_Login_Pro_Provider_Manager();
            $config = get_option("otp_login_pro_provider_{$gateway_id}", []);
            $config['enabled'] = true;
            
            // Create provider instance with config
            $providers = $provider_manager->get_all_providers();
            if (!isset($providers[$gateway_id])) {
                wp_send_json_error(['message' => 'Provider not found']);
            }
            
            $provider_class = $providers[$gateway_id]['class'];
            if (!class_exists($provider_class)) {
                wp_send_json_error(['message' => 'Provider class not found']);
            }
            
            $provider = new $provider_class($config);
            $result = $provider->send($number, 'Test message from OTP Login Pro. Code: 123456');
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
        } else {
            // Test gateway-based provider
            $config = [
                'username' => get_option("otp_login_pro_gateway_{$gateway_id}_username"),
                'password' => get_option("otp_login_pro_gateway_{$gateway_id}_password"),
                'sender' => get_option("otp_login_pro_gateway_{$gateway_id}_sender"),
            ];
            
            $result = OTP_Login_Pro_Gateway_Adapter::send_sms(
                $gateway_id,
                $number,
                'Test message from OTP Login Pro. Code: 123456',
                $config
            );
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
        }
    }
}

new OTP_Login_Pro_Gateway_Selector();
