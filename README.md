# OTP Login Pro - WordPress Plugin

## Description

**OTP Login Pro** is an enterprise-grade WordPress authentication plugin that enables passwordless login via One-Time Passwords (OTP) sent through SMS, Email, WhatsApp, and Voice calls. With 150+ features, advanced security, beautiful UI, and deep WordPress ecosystem integration, it's the complete solution for modern authentication.

## Features

### 🔐 Core Authentication (25+ Features)
- ✅ SMS OTP via Twilio, Vonage, AWS SNS, Kavenegar, Ghasedak
- ✅ Email OTP via WordPress Mail, SendGrid, Mailgun
- ✅ WhatsApp Business API integration
- ✅ Voice call OTP delivery
- ✅ Passwordless login
- ✅ Two-Factor Authentication (2FA)
- ✅ Multi-Factor Authentication (MFA)
- ✅ WebAuthn/FIDO2 biometric support
- ✅ TOTP (Google Authenticator) compatible
- ✅ Backup codes generation
- ✅ Device trust & remember me
- ✅ Magic link authentication

### 🎨 UI/UX & Design (20+ Features)
- ✅ 3 Pre-built themes (Modern, Minimal, Corporate)
- ✅ Mobile-first responsive design
- ✅ Dark mode support
- ✅ RTL language support
- ✅ Auto-detect country code
- ✅ Phone number formatting with flags
- ✅ SMS OTP auto-fill (Web OTP API)
- ✅ Countdown timer for OTP expiry
- ✅ WCAG 2.1 AA accessibility
- ✅ Toast notifications
- ✅ Sound & haptic feedback
- ✅ Smooth animations

### 🛡️ Security & Fraud Prevention (25+ Features)
- ✅ IP-based rate limiting
- ✅ User-based rate limiting
- ✅ Device fingerprinting
- ✅ Geographic IP restrictions
- ✅ Brute force protection
- ✅ Bot detection
- ✅ Google reCAPTCHA v2/v3
- ✅ hCaptcha integration
- ✅ Cloudflare Turnstile
- ✅ OTP encryption in database
- ✅ Secure session management
- ✅ CSRF protection
- ✅ Login attempt logging
- ✅ Suspicious activity alerts
- ✅ GDPR compliance tools

### 📊 Analytics & Reporting (12+ Features)
- ✅ Real-time analytics dashboard
- ✅ OTP success/failure rates
- ✅ Geographic login heatmap
- ✅ Device & browser statistics
- ✅ Peak usage time graphs
- ✅ Cost per SMS/email tracking
- ✅ Provider performance comparison
- ✅ Export to PDF/CSV/Excel

### 🔌 WordPress Integration (20+ Features)
- ✅ WooCommerce checkout OTP
- ✅ WooCommerce phone verification
- ✅ Elementor widget
- ✅ BuddyPress integration
- ✅ MemberPress support
- ✅ LearnDash integration
- ✅ Contact Form 7, Gravity Forms, WPForms
- ✅ bbPress forum protection

### 🚀 Advanced Features
- ✅ Comprehensive REST API
- ✅ Webhook integration
- ✅ Multi-site network support
- ✅ White-label options
- ✅ 100+ developer hooks
- ✅ Drag-and-drop email builder
- ✅ Auto-registration
- ✅ Multiple phone numbers per user

## Installation

1. Upload `otp-login-pro` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu
3. Go to **OTP Login Pro** > **Settings** to configure
4. Configure at least one SMS/Email provider
5. Add shortcode `[otp_login_form]` to any page

## Configuration

### Basic Setup

1. **Navigate to Settings**
   - Go to WordPress Admin > OTP Login Pro > Settings

2. **Configure OTP Settings**
   - Enable OTP Login
   - Choose authentication method (SMS/Email/Both)
   - Set OTP length (4-10 digits)
   - Set expiry time (default: 5 minutes)
   - Set cooldown period (default: 60 seconds)

3. **Configure Providers**
   - Go to OTP Login Pro > Providers
   - Add your Twilio or Vonage credentials for SMS
   - Configure email settings

### Provider Configuration

#### Twilio SMS
```
Account SID: Your Twilio Account SID
Auth Token: Your Twilio Auth Token
From Number: Your Twilio phone number
```

#### Kavenegar (Iranian)
```
API Key: Your Kavenegar API key
Sender: Your approved sender number
```

#### Email (WordPress Mail)
```
From Name: Your Site Name
From Email: noreply@yoursite.com
```

## Shortcodes

### Login Form
```
[otp_login_form theme="modern" redirect="/dashboard"]
```

**Attributes:**
- `theme` - modern, minimal, corporate (default: modern)
- `redirect` - URL to redirect after login
- `title` - Custom form title
- `method` - sms, email, both (default: both)

### Registration Form
```
[otp_register_form]
```

### Profile Manager
```
[otp_profile_manager]
```

### Phone Verification
```
[otp_phone_verify]
```

## API Endpoints

### Send OTP
```
POST /wp-json/otp-pro/v1/send
{
  "identifier": "user@example.com",
  "method": "email"
}
```

### Verify OTP
```
POST /wp-json/otp-pro/v1/verify
{
  "identifier": "user@example.com",
  "otp": "123456",
  "remember": true
}
```

### Resend OTP
```
POST /wp-json/otp-pro/v1/resend
{
  "identifier": "user@example.com"
}
```

## Developer Hooks

### Actions
```php
// After OTP sent
do_action('otp_login_pro_otp_sent', $identifier, $method, $user);

// After successful login
do_action('otp_login_pro_user_logged_in', $user);

// After user registration
do_action('otp_login_pro_user_registered', $user);

// On verification failure
do_action('otp_login_pro_verification_failed', $identifier, $otp);
```

### Filters
```php
// Modify OTP length
add_filter('otp_login_pro_otp_length', function($length, $user) {
    return 8; // Use 8 digits for admins
}, 10, 2);

// Modify redirect URL
add_filter('otp_login_pro_redirect_url', function($url, $user) {
    return home_url('/my-account');
}, 10, 2);

// Customize SMS message
add_filter('otp_login_pro_sms_message', function($message, $otp) {
    return "Your code is: {$otp}. Do not share!";
}, 10, 2);
```

## Database Tables

The plugin creates 10 custom tables:
- `wp_otp_logs` - OTP delivery logs
- `wp_otp_rate_limits` - Rate limiting data
- `wp_otp_trusted_devices` - Trusted device tokens
- `wp_otp_backup_codes` - 2FA backup codes
- `wp_otp_analytics` - Aggregated analytics
- `wp_otp_phone_numbers` - User phone numbers
- `wp_otp_settings` - Complex settings
- `wp_otp_credits` - Credit management
- `wp_otp_transactions` - Transaction history
- `wp_otp_sessions` - OTP sessions

## Security

- All OTPs are hashed using WordPress password hashing
- Session tokens use cryptographically secure random generation
- Rate limiting prevents brute force attacks
- Device fingerprinting tracks suspicious logins
- Automatic cleanup of expired data

## Performance

- Async OTP sending for non-blocking requests
- Database query optimization with proper indexing
- CDN-ready asset loading
- Lazy loading for heavy components
- Cache-friendly architecture

## Requirements

- PHP 7.4 or higher
- WordPress 5.8 or higher
- MySQL 5.6 or MariaDB 10.0 or higher

## Support

- Documentation: [https://example.com/docs](https://example.com/docs)
- Support Forum: [https://example.com/support](https://example.com/support)
- Email: support@example.com

## License

GPL v2 or later

## Credits

Developed by [Your Name]
