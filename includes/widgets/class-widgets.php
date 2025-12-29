<?php
/**
 * WordPress Widgets
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Widgets {
    
    public function __construct() {
        add_action('widgets_init', [$this, 'register_widgets']);
    }
    
    public function register_widgets() {
        register_widget('OTP_Login_Pro_Login_Widget');
    }
}

class OTP_Login_Pro_Login_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'otp_login_widget',
            __('OTP Login Form', 'otp-login-pro'),
            ['description' => __('Display OTP login form', 'otp-login-pro')]
        );
    }
    
    public function widget($args, $instance) {
        if (is_user_logged_in()) {
            return;
        }
        
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $shortcodes = new OTP_Login_Pro_Shortcodes();
        echo $shortcodes->login_form([]);
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Login', 'otp-login-pro');
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'otp-login-pro'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}
