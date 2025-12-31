<?php
/**
 * Universal Gateway Adapter
 * Integrates 118+ Iranian SMS gateways into OTP Login Pro
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Gateway_Adapter {
    
    private static $gateways_loaded = false;
    private static $available_gateways = [];
    
    /**
     * Load all gateway classes
     */
    public static function load_gateways() {
        if (self::$gateways_loaded) {
            return;
        }
        
        $gateways_dir = OTP_LOGIN_PRO_PATH . 'gateways/';
        
        if (!is_dir($gateways_dir)) {
            return;
        }
        
        // Load interface and trait first
        if (file_exists($gateways_dir . 'GatewayInterface.php')) {
            require_once $gateways_dir . 'GatewayInterface.php';
        }
        
        if (file_exists($gateways_dir . 'GatewayTrait.php')) {
            require_once $gateways_dir . 'GatewayTrait.php';
        }
        
        // Load all gateway files
        $files = glob($gateways_dir . '*.php');
        
        foreach ($files as $file) {
            $filename = basename($file);
            
            // Skip interface, trait, and logger
            if (in_array($filename, ['GatewayInterface.php', 'GatewayTrait.php', 'Logger.php'])) {
                continue;
            }
            
            require_once $file;
            
            // Get class name from filename
            $className = 'PW\\PWSMS\\Gateways\\' . str_replace('.php', '', $filename);
            
            if (class_exists($className)) {
                try {
                    $gateway_id = $className::id();
                    $gateway_name = $className::name();
                    
                    self::$available_gateways[$gateway_id] = [
                        'id' => $gateway_id,
                        'name' => $gateway_name,
                        'class' => $className,
                        'file' => $filename,
                    ];
                } catch (Exception $e) {
                    error_log("OTP Login Pro: Failed to load gateway {$className}: " . $e->getMessage());
                }
            }
        }
        
        self::$gateways_loaded = true;
    }
    
    /**
     * Get all available gateways
     */
    public static function get_all_gateways() {
        self::load_gateways();
        return self::$available_gateways;
    }
    
    /**
     * Get gateway by ID
     */
    public static function get_gateway($gateway_id) {
        self::load_gateways();
        return self::$available_gateways[$gateway_id] ?? null;
    }
    
    /**
     * Send SMS via gateway
     */
    public static function send_sms($gateway_id, $mobile, $message, $config = []) {
        self::load_gateways();
        
        $gateway_info = self::$available_gateways[$gateway_id] ?? null;
        
        if (!$gateway_info) {
            return [
                'success' => false,
                'message' => "Gateway {$gateway_id} not found",
            ];
        }
        
        try {
            $gateway = new $gateway_info['class']();
            
            // Set config
            $gateway->username = $config['username'] ?? get_option("otp_login_pro_gateway_{$gateway_id}_username", '');
            $gateway->password = $config['password'] ?? get_option("otp_login_pro_gateway_{$gateway_id}_password", '');
            $gateway->senderNumber = $config['sender'] ?? get_option("otp_login_pro_gateway_{$gateway_id}_sender", '');
            $gateway ->templateId = $config['templateId'] ?? get_option("otp_login_pro_gateway_{$gateway_id}_templateId", '');
            
            // Set message and mobile
            $gateway->message = $message;
            $gateway->mobile = is_array($mobile) ? $mobile : [$mobile];
            
            // Send
            $result = $gateway->send();
            
            if ($result === true) {
                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'gateway' => $gateway_id,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => is_string($result) ? $result : 'Unknown error',
                    'gateway' => $gateway_id,
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'gateway' => $gateway_id,
            ];
        }
    }
    
    /**
     * Create provider adapter for each gateway
     */
    public static function create_provider_adapters() {
        self::load_gateways();
        
        $adapters = [];
        
        foreach (self::$available_gateways as $gateway_id => $gateway_info) {
            $adapters[] = self::create_adapter_class($gateway_id, $gateway_info);
        }
        
        return $adapters;
    }
    
    /**
     * Create adapter class dynamically
     */
    private static function create_adapter_class($gateway_id, $gateway_info) {
        $class_name = 'OTP_Login_Pro_Provider_' . ucfirst($gateway_id);
        
        if (class_exists($class_name)) {
            return $class_name;
        }
        
        // Create dynamic class
        eval('
        class ' . $class_name . ' extends OTP_Login_Pro_Abstract_Provider {
            protected function init() {
                $this->name = "' . $gateway_info['name'] . '";
                $this->type = "sms";
                $this->cost_per_message = 0.003;
            }
            
            public function send($recipient, $message, $options = []) {
                return OTP_Login_Pro_Gateway_Adapter::send_sms(
                    "' . $gateway_id . '",
                    $recipient,
                    $message,
                    $this->config
                );
            }
            
            public function validate_config() {
                $errors = [];
                if (empty($this->config["username"]) && empty($this->config["password"])) {
                    $errors[] = "API Key required";
                }
                return ["valid" => empty($errors), "errors" => $errors];
            }
        }
        ');
        
        return $class_name;
    }
}

// Initialize on plugin load
add_action('plugins_loaded', ['OTP_Login_Pro_Gateway_Adapter', 'load_gateways'], 5);
