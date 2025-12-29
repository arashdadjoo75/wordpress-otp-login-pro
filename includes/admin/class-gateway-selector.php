<?php
/**
 * Gateway Selector Admin Page
 * Allows admin to choose and configure from 118+ SMS gateways
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
    
    public function render_page() {
        $gateways = OTP_Login_Pro_Gateway_Adapter::get_all_gateways();
        $active_gateway = get_option('otp_login_pro_active_gateway', 'kavenegar');
        
        ?>
        <div class="wrap">
            <h1><?php _e('SMS Gateway Configuration', 'otp-login-pro'); ?></h1>
            <p class="description"><?php _e('Choose from 118+ Iranian SMS providers', 'otp-login-pro'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('otp_gateway_settings', 'otp_gateway_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('Select Gateway', 'otp-login-pro'); ?></th>
                        <td>
                            <select name="otp_active_gateway" id="otp-active-gateway" class="regular-text">
                                <?php foreach ($gateways as $gateway): ?>
                                    <option value="<?php echo esc_attr($gateway['id']); ?>" 
                                            <?php selected($active_gateway, $gateway['id']); ?>>
                                        <?php echo esc_html($gateway['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php printf(__('%d gateways available', 'otp-login-pro'), count($gateways)); ?>
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
                                   value="<?php echo esc_attr(get_option("otp_login_pro_gateway_{$active_gateway}_username")); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('Enter your API key or username', 'otp-login-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('API Secret / Password', 'otp-login-pro'); ?></th>
                        <td>
                            <input type="password" 
                                   name="otp_gateway_password" 
                                   value="<?php echo esc_attr(get_option("otp_login_pro_gateway_{$active_gateway}_password")); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('Enter your API secret or password (if required)', 'otp-login-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('Sender Number / From', 'otp-login-pro'); ?></th>
                        <td>
                            <input type="text" 
                                   name="otp_gateway_sender" 
                                   value="<?php echo esc_attr(get_option("otp_login_pro_gateway_{$active_gateway}_sender")); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('Your approved sender number', 'otp-login-pro'); ?></p>
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
                </table>
                
                <?php submit_button(__('Save Gateway Settings', 'otp-login-pro')); ?>
            </form>
            
            <hr />
            
            <h2><?php _e('Available Gateways', 'otp-login-pro'); ?></h2>
            <div class="otp-gateways-grid">
                <?php foreach ($gateways as $gateway): ?>
                    <div class="otp-gateway-card <?php echo $gateway['id'] === $active_gateway ? 'active' : ''; ?>">
                        <h4><?php echo esc_html($gateway['name']); ?></h4>
                        <p><code><?php echo esc_html($gateway['id']); ?></code></p>
                        <?php if ($gateway['id'] === $active_gateway): ?>
                            <span class="dashicons dashicons-yes-alt" style="color:green;"></span>
                            <?php _e('Active', 'otp-login-pro'); ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
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
            $('#otp-test-gateway-btn').on('click', function() {
                var btn = $(this);
                var number = $('#otp-test-number').val();
                var gateway = $('#otp-active-gateway').val();
                
                if (!number) {
                    alert('<?php _e('Please enter a test number', 'otp-login-pro'); ?>');
                    return;
                }
                
                btn.prop('disabled', true).text('<?php _e('Sending...', 'otp-login-pro'); ?>');
                
                $.post(ajaxurl, {
                    action: 'otp_pro_test_gateway',
                    nonce: '<?php echo wp_create_nonce('otp_test_gateway'); ?>',
                    gateway: gateway,
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
        
        if ($gateway_id) {
            update_option('otp_login_pro_active_gateway', $gateway_id);
            update_option("otp_login_pro_gateway_{$gateway_id}_username", sanitize_text_field($_POST['otp_gateway_username'] ?? ''));
            update_option("otp_login_pro_gateway_{$gateway_id}_password", sanitize_text_field($_POST['otp_gateway_password'] ?? ''));
            update_option("otp_login_pro_gateway_{$gateway_id}_sender", sanitize_text_field($_POST['otp_gateway_sender'] ?? ''));
            
            add_settings_error('otp_gateway_settings', 'settings_updated', __('Settings saved successfully', 'otp-login-pro'), 'success');
        }
    }
    
    public function ajax_test_gateway() {
        check_ajax_referer('otp_test_gateway', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $gateway_id = sanitize_text_field($_POST['gateway'] ?? '');
        $number = sanitize_text_field($_POST['number'] ?? '');
        
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

new OTP_Login_Pro_Gateway_Selector();
