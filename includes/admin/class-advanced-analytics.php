<?php
/**
 * Advanced Analytics with Charts
 * Chart.js integration for beautiful visualizations
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Advanced_Analytics {
    
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_otp_get_chart_data', [$this, 'ajax_get_chart_data']);
    }
    
    /**
     * Enqueue Chart.js
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'otp-login-pro') === false) {
            return;
        }
        
        // Chart.js from CDN
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );
        
        wp_enqueue_script(
            'otp-charts',
            OTP_LOGIN_PRO_URL . 'assets/js/charts.js',
            ['chartjs', 'jquery'],
            OTP_LOGIN_PRO_VERSION,
            true
        );
        
        wp_localize_script('otp-charts', 'otpCharts', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('otp_charts'),
        ]);
    }
    
    /**
     * Get chart data via AJAX
     */
    public function ajax_get_chart_data() {
        check_ajax_referer('otp_charts', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $chart_type = sanitize_text_field($_POST['chart_type'] ?? 'daily_activity');
        $days = intval($_POST['days'] ?? 30);
        
        $data = $this->get_chart_data($chart_type, $days);
        
        wp_send_json_success($data);
    }
    
    /**
     * Get chart data
     */
    private function get_chart_data($type, $days = 30) {
        global $wpdb;
        
        switch ($type) {
            case 'daily_activity':
                return $this->get_daily_activity($days);
            
            case 'success_rate':
                return $this->get_success_rate($days);
            
            case 'provider_comparison':
                return $this->get_provider_comparison($days);
            
            case 'geographic_distribution':
                return $this->get_geographic_distribution($days);
            
            case 'hourly_distribution':
                return $this->get_hourly_distribution($days);
            
            case 'device_breakdown':
                return $this->get_device_breakdown($days);
            
            default:
                return [];
        }
    }
    
    /**
     * Daily activity chart data
     */
    private function get_daily_activity($days) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM {$wpdb->prefix}otp_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", $days));
        
        $labels = [];
        $sent = [];
        $verified = [];
        $failed = [];
        
        foreach ($results as $row) {
            $labels[] = date('M d', strtotime($row->date));
            $sent[] = intval($row->sent);
            $verified[] = intval($row->verified);
            $failed[] = intval($row->failed);
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Sent',
                    'data' => $sent,
                    'borderColor' => 'rgb(54, 162, 235)',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Verified',
                    'data' => $verified,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Failed',
                    'data' => $failed,
                    'borderColor' => 'rgb(255, 99, 132)',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.1)',
                    'tension' => 0.4,
                ],
            ],
        ];
    }
    
    /**
     * Success rate chart
     */
    private function get_success_rate($days) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(created_at) as date,
                ROUND((SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as success_rate
            FROM {$wpdb->prefix}otp_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", $days));
        
        $labels = [];
        $data = [];
        
        foreach ($results as $row) {
            $labels[] = date('M d', strtotime($row->date));
            $data[] = floatval($row->success_rate);
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Success Rate (%)',
                    'data' => $data,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ];
    }
    
    /**
     * Provider comparison
     */
    private function get_provider_comparison($days) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                provider,
                COUNT(*) as count,
                SUM(cost) as total_cost
            FROM {$wpdb->prefix}otp_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND provider IS NOT NULL
            GROUP BY provider
            ORDER BY count DESC
            LIMIT 10
        ", $days));
        
        $labels = [];
        $counts = [];
        $costs = [];
        
        foreach ($results as $row) {
            $labels[] = $row->provider;
            $counts[] = intval($row->count);
            $costs[] = floatval($row->total_cost);
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Messages Sent',
                    'data' => $counts,
                    'backgroundColor' => [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                    ],
                ],
            ],
        ];
    }
    
    /**
     * Hourly distribution
     */
    private function get_hourly_distribution($days) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                HOUR(created_at) as hour,
                COUNT(*) as count
            FROM {$wpdb->prefix}otp_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY HOUR(created_at)
            ORDER BY hour ASC
        ", $days));
        
        $labels = range(0, 23);
        $data = array_fill(0, 24, 0);
        
        foreach ($results as $row) {
            $data[intval($row->hour)] = intval($row->count);
        }
        
        return [
            'labels' => array_map(function($h) { return sprintf('%02d:00', $h); }, $labels),
            'datasets' => [
                [
                    'label' => 'OTP Requests',
                    'data' => $data,
                    'backgroundColor' => 'rgba(153, 102, 255, 0.6)',
                    'borderColor' => 'rgb(153, 102, 255)',
                    'borderWidth' => 1,
                ],
            ],
        ];
    }
    
    /**
     * Device breakdown
     */
    private function get_device_breakdown($days) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                CASE 
                    WHEN user_agent LIKE '%%Mobile%%' THEN 'Mobile'
                    WHEN user_agent LIKE '%%Tablet%%' THEN 'Tablet'
                    ELSE 'Desktop'
                END as device_type,
                COUNT(*) as count
            FROM {$wpdb->prefix}otp_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY device_type
        ", $days));
        
        $labels = [];
        $data = [];
        
        foreach ($results as $row) {
            $labels[] = $row->device_type;
            $data[] = intval($row->count);
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Devices',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                    ],
                ],
            ],
        ];
    }
    
    /**
     * Geographic distribution (placeholder - needs GeoIP)
     */
    private function get_geographic_distribution($days) {
        return [
            'labels' => ['Iran', 'USA', 'UK', 'Canada', 'Other'],
            'datasets' => [
                [
                    'label' => 'By Country',
                    'data' => [45, 20, 15, 10, 10],
                    'backgroundColor' => [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                    ],
                ],
            ],
        ];
    }
}

new OTP_Login_Pro_Advanced_Analytics();
