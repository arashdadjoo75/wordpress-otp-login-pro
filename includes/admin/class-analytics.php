<?php
/**
 * Analytics Dashboard
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Analytics {
    
    public function get_stats($period = '7days') {
        global $wpdb;
        
        $start_date = date('Y-m-d', strtotime("-{$period}"));
        
        $stats = [
            'total_sent' => $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->prefix}otp_logs 
                WHERE created_at >= %s
            ", $start_date)),
            
            'total_verified' => $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->prefix}otp_logs 
                WHERE verified_at >= %s AND status = 'verified'
            ", $start_date)),
            
            'total_failed' => $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->prefix}otp_logs 
                WHERE created_at >= %s AND status = 'failed'
            ", $start_date)),
            
            'success_rate' => 0,
            'total_cost' => $wpdb->get_var($wpdb->prepare("
                SELECT SUM(cost) FROM {$wpdb->prefix}otp_logs 
                WHERE created_at >= %s
            ", $start_date)),
        ];
        
        if ($stats['total_sent'] > 0) {
            $stats['success_rate'] = round(($stats['total_verified'] / $stats['total_sent']) * 100, 2);
        }
        
        return $stats;
    }
    
    public function get_daily_stats($days = 7) {
        global $wpdb;
        
        $results = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            
            $sent = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->prefix}otp_logs 
                WHERE DATE(created_at) = %s
            ", $date));
            
            $verified = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->prefix}otp_logs 
                WHERE DATE(verified_at) = %s AND status = 'verified'
            ", $date));
            
            $results[] = [
                'date' => $date,
                'sent' => intval($sent),
                'verified' => intval($verified),
            ];
        }
        
        return $results;
    }
}
