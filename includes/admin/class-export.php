<?php
/**
 * Report Export (PDF/CSV)
 * Export analytics and logs to multiple formats
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Export {
    
    public function __construct() {
        add_action('admin_post_otp_export_report', [$this, 'export_report']);
    }
    
    /**
     * Export report
     */
    public function export_report() {
        check_admin_referer('otp_export_report');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $format = sanitize_text_field($_GET['format'] ?? 'csv');
        $type = sanitize_text_field($_GET['type'] ?? 'logs');
        $days = intval($_GET['days'] ?? 30);
        
        switch ($format) {
            case 'csv':
                $this->export_csv($type, $days);
                break;
            case 'pdf':
                $this->export_pdf($type, $days);
                break;
            default:
                wp_die('Invalid format');
        }
    }
    
    /**
     * Export to CSV
     */
    private function export_csv($type, $days) {
        global $wpdb;
        
        $filename = "otp-{$type}-" . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        
        if ($type === 'logs') {
            // Headers
            fputcsv($output, ['Date', 'Identifier', 'Method', 'Provider', 'Status', 'IP Address', 'Cost']);
            
            // Data
            $logs = $wpdb->get_results($wpdb->prepare("
                SELECT created_at, identifier, method, provider, status, ip_address, cost
                FROM {$wpdb->prefix}otp_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                ORDER BY created_at DESC
                LIMIT 10000
            ", $days));
            
            foreach ($logs as $log) {
                fputcsv($output, [
                    $log->created_at,
                    $log->identifier,
                    $log->method,
                    $log->provider,
                    $log->status,
                    $log->ip_address,
                    $log->cost
                ]);
            }
            
        } elseif ($type === 'analytics') {
            // Headers
            fputcsv($output, ['Date', 'Total Sent', 'Verified', 'Failed', 'Success Rate', 'Total Cost']);
            
            // Data
            $analytics = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total_sent,
                    SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    ROUND((SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as success_rate,
                    SUM(cost) as total_cost
                FROM {$wpdb->prefix}otp_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ", $days));
            
            foreach ($analytics as $row) {
                fputcsv($output, [
                    $row->date,
                    $row->total_sent,
                    $row->verified,
                    $row->failed,
                    $row->success_rate . '%',
                    '$' . number_format($row->total_cost, 2)
                ]);
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export to PDF (simple HTML to PDF)
     */
    private function export_pdf($type, $days) {
        global $wpdb;
        
        $filename = "otp-{$type}-" . date('Y-m-d') . '.pdf';
        
        // Simple HTML-based PDF using browser print
        header('Content-Type: text/html; charset=utf-8');
        
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        echo '<title>' . esc_html($filename) . '</title>';
        echo '<style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
            th { background-color: #667eea; color: white; }
            tr:nth-child(even) { background-color: #f8f9fa; }
            .meta { color: #666; margin-bottom: 20px; }
            @media print {
                body { margin: 0; }
                button { display: none; }
            }
        </style>';
        echo '</head><body>';
        
        echo '<h1>OTP Login Pro Report</h1>';
        echo '<div class="meta">Generated on: ' . date('F j, Y, g:i a') . '<br>';
        echo 'Period: Last ' . $days . ' days</div>';
        
        echo '<button onclick="window.print()">Print / Save as PDF</button>';
        
        if ($type === 'logs') {
            echo '<h2>OTP Logs</h2><table>';
            echo '<tr><th>Date</th><th>Identifier</th><th>Method</th><th>Provider</th><th>Status</th><th>IP</th><th>Cost</th></tr>';
            
            $logs = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}otp_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                ORDER BY created_at DESC
                LIMIT 1000
            ", $days));
            
            foreach ($logs as $log) {
                echo '<tr>';
                echo '<td>' . esc_html($log->created_at) . '</td>';
                echo '<td>' . esc_html($log->identifier) . '</td>';
                echo '<td>' . esc_html($log->method) . '</td>';
                echo '<td>' . esc_html($log->provider) . '</td>';
                echo '<td>' . esc_html($log->status) . '</td>';
                echo '<td>' . esc_html($log->ip_address) . '</td>';
                echo '<td>$' . number_format($log->cost, 4) . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        }
        
        echo '</body></html>';
        exit;
    }
}

new OTP_Login_Pro_Export();
