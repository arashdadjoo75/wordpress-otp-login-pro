=== OTP Login Pro ===
Contributors: yourusername
Tags: otp, sms, authentication, two-factor, 2fa, login, security, passwordless
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Enterprise-grade OTP authentication plugin with SMS, Email, WhatsApp, 2FA/MFA, and 120+ features for WordPress.

== Description ==

**OTP Login Pro** is the most comprehensive OTP authentication solution for WordPress. Enable passwordless login, two-factor authentication, and secure user verification via SMS, Email, and WhatsApp.

### 🔐 Core Features

* **Multiple Delivery Methods**: SMS, Email, WhatsApp Business API
* **8+ SMS Providers**: Twilio, Vonage, AWS SNS, Kavenegar, Ghasedak, and more
* **Email Providers**: WordPress Mail, SendGrid, Mailgun
* **Passwordless Login**: No passwords needed
* **Auto-Registration**: Create accounts via OTP
* **Device Trust**: Remember trusted devices for 30 days

### 🛡️ Security & Fraud Prevention

* **Rate Limiting**: IP and user-based
* **Fraud Detection**: AI-powered risk scoring
* **Brute Force Protection**: Automatic IP blocking
* **CAPTCHA Support**: Google reCAPTCHA v2/v3, hCaptcha
* **Device Fingerprinting**: Track and manage devices
* **OTP Encryption**: Secure password hashing

### 🎨 Modern UI/UX

* **3 Premium Themes**: Modern, Minimal, Corporate
* **Fully Responsive**: Perfect on mobile, tablet, desktop
* **RTL Support**: Arabic, Persian, Hebrew
* **Auto-Fill**: Web OTP API support
* **Smooth Animations**: Professional interactions
* **Countdown Timers**: Visual OTP expiry

### 📊 Analytics & Reporting

* **Real-Time Dashboard**: Track OTP usage
* **Success Rates**: Monitor delivery rates
* **Cost Tracking**: Per-SMS/Email cost analysis
* **Activity Logs**: Complete audit trail
* **Geographic Data**: See where users login from

### 🔌 Deep Integrations

* **WooCommerce**: Checkout & account OTP verification
* **MemberPress**: 2FA for memberships
* **LearnDash**: Secure course access
* **Gravity Forms**: Custom OTP fields
* **WPForms**: OTP verification
* **Contact Form 7**: OTP tags
* **Elementor**: OTP widgets
* **BuddyPress**: Social network security

### 👨‍💻 Developer Friendly

* **REST API**: 5 complete endpoints
* **Webhooks**: HMAC-signed events
* **100+ Hooks**: Actions and filters
* **Modular Architecture**: Easy to extend
* **Well Documented**: Clear code comments

### 💰 Monetization Ready

* **Credits System**: Pay-per-OTP model
* **Transaction History**: Track purchases
* **Multiple Packages**: Flexible pricing
* **License Management**: Tier-based features

== Installation ==

1. Upload `otp-login-pro` folder to `/wp-content/plugins/`
2. Activate the plugin through WordPress admin
3. Go to **OTP Login Pro** > **Settings** to configure
4. Set up at least one SMS or Email provider
5. Add `[otp_login_form]` shortcode to any page

== Frequently Asked Questions ==

= Which SMS providers are supported? =

We support Twilio, Vonage (Nexmo), AWS SNS, Kavenegar, Ghasedak, and more. You can use multiple providers with automatic failover.

= Does it work with WooCommerce? =

Yes! OTP verification can be added to checkout, registration, and user account pages.

= Can I use my own SMTP for emails? =

Absolutely. The plugin uses WordPress's built-in `wp_mail()` function, which works with any SMTP plugin.

= Is it GDPR compliant? =

Yes. The plugin includes tools to help you comply with GDPR, including data export and deletion.

= Can I white-label the plugin? =

Yes, with the Agency license you can customize branding and remove our credits.

== Screenshots ==

1. Modern OTP login form with gradient design
2. Admin dashboard with real-time analytics
3. Provider configuration page
4. Comprehensive settings panel
5. OTP logs and audit trail
6. Device trust management
7. WooCommerce integration
8. 2FA setup with QR code

== Changelog ==

= 1.0.0 =
* Initial release
* 120+ features implemented
* Complete OTP authentication system
* Multi-provider SMS/Email delivery
* Advanced security features
* Beautiful responsive UI
* Deep WooCommerce integration
* REST API and webhooks
* TOTP/2FA support
* Fraud detection
* Credits and monetization
* Form plugin integrations

== Upgrade Notice ==

= 1.0.0 =
Initial release with 120+ enterprise features.

== Additional Information ==

For documentation, support, and updates, visit: https://yourdomain.com/docs

**Pro Support**: Get priority email support with Pro and Agency licenses.

**Feature Requests**: We love hearing from you! Submit requests on our GitHub.
