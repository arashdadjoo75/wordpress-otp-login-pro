<?php
/**
 * A/B Testing Framework
 * Test different OTP flows and optimize conversion
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_AB_Testing {
    
    private $experiments = [];
    
    public function __construct() {
        add_action('init', [$this, 'load_experiments']);
        add_action('wp_ajax_otp_ab_track_event', [$this, 'ajax_track_event']);
        add_action('wp_ajax_nopriv_otp_ab_track_event', [$this, 'ajax_track_event']);
    }
    
    /**
     * Load active experiments
     */
    public function load_experiments() {
        $this->experiments = get_option('otp_ab_experiments', []);
    }
    
    /**
     * Create new experiment
     */
    public static function create_experiment($name, $variants) {
        $experiments = get_option('otp_ab_experiments', []);
        
        $experiment_id = sanitize_title($name);
        
        $experiments[$experiment_id] = [
            'id' => $experiment_id,
            'name' => $name,
            'variants' => $variants,
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'traffic_split' => array_fill(0, count($variants), 100 / count($variants)),
        ];
        
        update_option('otp_ab_experiments', $experiments);
        
        return $experiment_id;
    }
    
    /**
     * Get variant for user
     */
    public static function get_variant($experiment_id, $user_identifier = null) {
        $experiments = get_option('otp_ab_experiments', []);
        
        if (!isset($experiments[$experiment_id])) {
            return null;
        }
        
        $experiment = $experiments[$experiment_id];
        
        if ($experiment['status'] !== 'active') {
            return null;
        }
        
        // Check if user has assigned variant
        $user_id = $user_identifier ?? self::get_user_identifier();
        $assignment_key = 'otp_ab_' . $experiment_id . '_' . $user_id;
        
        $assigned = get_transient($assignment_key);
        
        if ($assigned !== false) {
            return $assigned;
        }
        
        // Assign variant based on traffic split
        $variant = self::assign_variant($experiment['variants'], $experiment['traffic_split']);
        
        // Store assignment (persistent for 30 days)
        set_transient($assignment_key, $variant, 30 * DAY_IN_SECONDS);
        
        // Track assignment
        self::track_event($experiment_id, 'assigned', $variant, $user_id);
        
        return $variant;
    }
    
    /**
     * Assign variant based on traffic split
     */
    private static function assign_variant($variants, $traffic_split) {
        $random = wp_rand(1, 100);
        $cumulative = 0;
        
        foreach ($traffic_split as $index => $percentage) {
            $cumulative += $percentage;
            if ($random <= $cumulative) {
                return $variants[$index];
            }
        }
        
        return $variants[0];
    }
    
    /**
     * Track event
     */
    public static function track_event($experiment_id, $event, $variant, $user_id = null) {
        global $wpdb;
        
        $user_id = $user_id ?? self::get_user_identifier();
        
        $wpdb->insert(
            "{$wpdb->prefix}otp_ab_events",
            [
                'experiment_id' => $experiment_id,
                'variant' => $variant,
                'event' => $event,
                'user_id' => $user_id,
                'created_at' => current_time('mysql'),
            ]
        );
    }
    
    /**
     * AJAX track event
     */
    public function ajax_track_event() {
        $experiment_id = sanitize_text_field($_POST['experiment_id'] ?? '');
        $event = sanitize_text_field($_POST['event'] ?? '');
        $variant = sanitize_text_field($_POST['variant'] ?? '');
        
        self::track_event($experiment_id, $event, $variant);
        
        wp_send_json_success();
    }
    
    /**
     * Get experiment results
     */
    public static function get_results($experiment_id) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                variant,
                event,
                COUNT(*) as count
            FROM {$wpdb->prefix}otp_ab_events
            WHERE experiment_id = %s
            GROUP BY variant, event
        ", $experiment_id));
        
        $data = [];
        
        foreach ($results as $row) {
            if (!isset($data[$row->variant])) {
                $data[$row->variant] = [];
            }
            $data[$row->variant][$row->event] = intval($row->count);
        }
        
        // Calculate conversion rates
        foreach ($data as $variant => &$events) {
            $assigned = $events['assigned'] ?? 1;
            $converted = $events['converted'] ?? 0;
            $events['conversion_rate'] = ($converted / $assigned) * 100;
        }
        
        return $data;
    }
    
    /**
     * Get user identifier
     */
    private static function get_user_identifier() {
        if (is_user_logged_in()) {
            return 'user_' . get_current_user_id();
        }
        
        // Use IP + User Agent as identifier
        return 'guest_' . md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
    }
    
    /**
     * Create AB testing table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'otp_ab_events';
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            experiment_id varchar(100) NOT NULL,
            variant varchar(50) NOT NULL,
            event varchar(50) NOT NULL,
            user_id varchar(100) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY experiment_variant (experiment_id, variant),
            KEY event (event), 
            KEY created_at (created_at)
        ) {$wpdb->get_charset_collate()};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize
new OTP_Login_Pro_AB_Testing();

// Create table on activation
register_activation_hook(OTP_LOGIN_PRO_FILE, ['OTP_Login_Pro_AB_Testing', 'create_table']);

// Example usage:
// $variant = OTP_Login_Pro_AB_Testing::get_variant('otp_length_test');
// if ($variant === 'short') { $length = 4; } else { $length = 6; }
