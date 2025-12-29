<?php
/**
 * Production Configuration Guide
 * Security & Performance Checklist
 */

if (!defined('ABSPATH')) exit;

class OTP_Login_Pro_Production_Config {
    
    /**
     * Get production readiness checklist
     */
    public static function get_checklist() {
        $security_status = OTP_Login_Pro_Security_Config::get_security_status();
        $performance = OTP_Login_Pro_Performance::get_metrics();
        
        return [
            'security' => [
                'items' => [
                    [
                        'name' => 'Database Table Prefix Changed',
                        'status' => self::check_custom_table_prefix(),
                        'action' => 'Update otp_login_pro_custom_table_prefix option',
                        'critical' => false,
                    ],
                    [
                        'name' => 'Rate Limiting Enabled',
                        'status' => $security_status['rate_limiting'],
                        'action' => 'Enable via Settings > Security',
                        'critical' => true,
                    ],
                    [
                        'name' => 'Fraud Detection Configured',
                        'status' => $security_status['fraud_detection'],
                        'action' => 'Enable fraud detection alerts',
                        'critical' => true,
                    ],
                    [
                        'name' => 'CAPTCHA Setup',
                        'status' => $security_status['captcha'] && self::check_captcha_keys(),
                        'action' => 'Add reCAPTCHA API keys',
                        'critical' => true,
                    ],
                    [
                        'name' => 'Webhook Signatures Enabled',
                        'status' => $security_status['webhook_signatures'],
                        'action' => 'Auto-configured on activation',
                        'critical' => false,
                    ],
                    [
                        'name' => 'License System Configured',
                        'status' => self::check_license(),
                        'action' => 'Activate premium license',
                        'critical' => false,
                    ],
                ],
                'score' => OTP_Login_Pro_Security_Config::get_security_score(),
            ],
            'performance' => [
                'items' => [
                    [
                        'name' => 'Object Caching Enabled',
                        'status' => $performance['cache_enabled'],
                        'action' => 'Install Redis or Memcached',
                        'critical' => false,
                    ],
                    [
                        'name' => 'CDN Configured',
                        'status' => $performance['cdn_enabled'],
                        'action' => 'Set otp_login_pro_cdn_url option',
                        'critical' => false,
                    ],
                    [
                        'name' => 'Cron Jobs Running',
                        'status' => $performance['cron_optimized'],
                        'action' => 'Verify wp-cron is working',
                        'critical' => true,
                    ],
                    [
                        'name' => 'Database Optimized',
                        'status' => $performance['db_optimized'],
                        'action' => 'Indexes created automatically',
                        'critical' => true,
                    ],
                ],
            ],
            'configuration' => [
                'items' => [
                    [
                        'name' => 'SMS Gateway Selected',
                        'status' => self::check_gateway_configured(),
                        'action' => 'Go to SMS Gateways and select provider',
                        'critical' => true,
                    ],
                    [
                        'name' => 'Email Provider Configured',
                        'status' => true, // wp_mail always available
                        'action' => 'WordPress Mail ready',
                        'critical' => true,
                    ],
                    [
                        'name' => 'OTP Settings Configured',
                        'status' => self::check_otp_settings(),
                        'action' => 'Configure OTP length, expiry, cooldown',
                        'critical' => true,
                    ],
                ],
            ],
        ];
    }
    
    /**
     * Check if custom table prefix is set
     */
    private static function check_custom_table_prefix() {
        $prefix = get_option('otp_login_pro_custom_table_prefix', '');
        return !empty($prefix) && $prefix !== 'wp_';
    }
    
    /**
     * Check CAPTCHA keys
     */
    private static function check_captcha_keys() {
        $site_key = get_option('otp_login_pro_captcha_site_key', '');
        $secret_key = get_option('otp_login_pro_captcha_secret_key', '');
        return !empty($site_key) && !empty($secret_key);
    }
    
    /**
     * Check license
     */
    private static function check_license() {
        return OTP_Login_Pro_License_Manager::is_valid();
    }
    
    /**
     * Check gateway configured
     */
    private static function check_gateway_configured() {
        $gateway = get_option('otp_login_pro_active_gateway', '');
        $username = get_option("otp_login_pro_gateway_{$gateway}_username", '');
        return !empty($gateway) && !empty($username);
    }
    
    /**
     * Check OTP settings
     */
    private static function check_otp_settings() {
        $enabled = get_option('otp_login_pro_enabled', false);
        $length = get_option('otp_login_pro_otp_length', 6);
        $expiry = get_option('otp_login_pro_expiry', 300);
        return $enabled && $length >= 4 && $expiry >= 60;
    }
    
    /**
     * Get production readiness score
     */
    public static function get_readiness_score() {
        $checklist = self::get_checklist();
        $total = 0;
        $completed = 0;
        
        foreach ($checklist as $category => $data) {
            foreach ($data['items'] as $item) {
                $total++;
                if ($item['status'] && $item['critical']) {
                    $completed += 2; // Critical items worth double
                    $total++; // Adjust total for weight
                } elseif ($item['status']) {
                    $completed++;
                }
            }
        }
        
        return $total > 0 ? round(($completed / $total) * 100) : 0;
    }
}
