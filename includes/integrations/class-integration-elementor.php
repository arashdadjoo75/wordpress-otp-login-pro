<?php
// Elementor Widget
if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Integration_Elementor {
    public function __construct() {
        add_action('elementor/widgets/widgets_registered', [$this, 'register_widgets']);
    }
    
    public function register_widgets() {
        // Widget registration
    }
}
