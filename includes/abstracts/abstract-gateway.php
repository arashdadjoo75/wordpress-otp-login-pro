<?php
/**
 * Abstract Payment Gateway
 * Base class for payment gateways (for credit purchases)
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class OTP_Login_Pro_Abstract_Gateway {
    
    protected $id;
    protected $name;
    protected $description;
    protected $enabled = false;
    protected $supports = [];
    
    abstract public function process_payment($amount, $user_id, $return_url);
    abstract public function process_webhook($request_data);
    abstract public function validate_config();
    
    public function is_enabled() {
        return $this->enabled;
    }
    
    public function supports($feature) {
        return in_array($feature, $this->supports);
    }
}
