<?php
/**
 * wp-config.php Production Recommendations
 * 
 * Add these constants to your wp-config.php for optimal performance:
 */

// ========================================
// SECURITY
// ========================================

/**
 * Change database table prefix
 * Default: wp_
 * Recommended: random string
 */
$table_prefix = 'otppro_' . wp_generate_password(5, false) . '_';

/**
 * Disable file editing
 */
define('DISALLOW_FILE_EDIT', true);

/**
 * Force SSL for admin
 */
define('FORCE_SSL_ADMIN', true);

/**
 * Set secure authentication keys
 * Generate new keys: https://api.wordpress.org/secret-key/1.1/salt/
 */
// ... (add your secure keys here)

// ========================================
// PERFORMANCE
// ========================================

/**
 * Enable object caching
 * Install Redis or Memcached plugin first
 */
define('WP_CACHE', true);

/**
 * Optimize WordPress memory
 */
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');

/**
 * Configure WP-Cron
 * For high-traffic sites, disable WP-Cron and use system cron
 */
// define('DISABLE_WP_CRON', true);
// Then add to system crontab:
// */5 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1

/**
 * Optimize revisions
 */
define('WP_POST_REVISIONS', 3);
define('AUTOSAVE_INTERVAL', 300); // 5 minutes

/**
 * Optimize database
 */
define('EMPTY_TRASH_DAYS', 7);

// ========================================
// DEBUGGING (Disable in production!)
// ========================================

/**
 * Debug mode - MUST be false in production
 */
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', false);

// ========================================
// OTP LOGIN PRO SPECIFIC
// ========================================

/**
 * Custom table prefix for OTP tables
 */
define('OTP_LOGIN_PRO_TABLE_PREFIX', 'otppro_');

/**
 * Enable advanced fraud detection
 */
define('OTP_LOGIN_PRO_FRAUD_DETECTION', true);

/**
 * CDN URL for assets
 */
define('OTP_LOGIN_PRO_CDN_URL', 'https://cdn.yoursite.com');

/**
 * Enable query monitoring (dev only)
 * Requires WP_DEBUG to be true
 */
// define('SAVEQUERIES', true);

/**
 * Max OTP attempts before blocking
 */
define('OTP_LOGIN_PRO_MAX_ATTEMPTS', 3);

/**
 * Rate limit window (seconds)
 */
define('OTP_LOGIN_PRO_RATE_LIMIT_WINDOW', 300); // 5 minutes

/**
 * OTP expiry (seconds)
 */
define('OTP_LOGIN_PRO_DEFAULT_EXPIRY', 300); // 5 minutes

// ========================================
// REDIS CONFIGURATION (Optional)
// ========================================

/**
 * If using Redis for object caching
 */
/*
define('WP_REDIS_HOST', '127.0.0.1');
define('WP_REDIS_PORT', 6379);
define('WP_REDIS_PASSWORD', '');
define('WP_REDIS_DATABASE', 0);
define('WP_REDIS_TIMEOUT', 1);
define('WP_REDIS_READ_TIMEOUT', 1);
*/

// ========================================
// SERVER CONFIGURATION
// ========================================

/**
 * Recommended PHP settings (php.ini):
 * 
 * memory_limit = 256M
 * max_execution_time = 300
 * max_input_time = 300
 * post_max_size = 32M
 * upload_max_filesize = 32M
 * max_input_vars = 3000
 * 
 * For Redis:
 * session.save_handler = redis
 * session.save_path = "tcp://127.0.0.1:6379"
 */

/**
 * Recommended MySQL settings:
 * 
 * innodb_buffer_pool_size = 1G (or 70% of available RAM)
 * max_connections = 200
 * query_cache_size = 64M
 * tmp_table_size = 64M
 * max_heap_table_size = 64M
 */

// ========================================
// NGINX CONFIGURATION (Optional)
// ========================================

/**
 * Add to nginx.conf for rate limiting:
 * 
 * limit_req_zone $binary_remote_addr zone=otp_limit:10m rate=5r/m;
 * 
 * Then in your location block:
 * location /wp-json/otp-pro/ {
 *     limit_req zone=otp_limit burst=10 nodelay;
 *     try_files $uri $uri/ /index.php?$args;
 * }
 */

// ========================================
// APACHE .htaccess (Optional)
// ========================================

/**
 * Add to .htaccess for security:
 * 
 * # Prevent directory browsing
 * Options -Indexes
 * 
 * # Protect wp-config.php
 * <files wp-config.php>
 * order allow,deny
 * deny from all
 * </files>
 * 
 * # Rate limiting (requires mod_ratelimit)
 * <Location "/wp-json/otp-pro/">
 *     SetOutputFilter RATE_LIMIT
 *     SetEnv rate-limit 400
 * </Location>
 */
