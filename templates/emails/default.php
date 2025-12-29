<?php
/**
 * Default Email Template
 * Variables available: $otp_code, $expiry_minutes, $site_name, $site_url
 */

$otp_code = $otp_code ?? '123456';
$expiry_minutes = isset($options['expiry']) ? intval($options['expiry']) / 60 : 5;
$site_name = get_bloginfo('name');
$site_url = home_url();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f4f7fa;font-family:Arial,sans-serif">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fa;padding:40px 20px">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1)">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:40px 30px;text-align:center">
                            <h1 style="margin:0;color:#fff;font-size:28px"><?php echo esc_html($site_name); ?></h1>
                            <p style="margin:10px 0 0;color:rgba(255,255,255,0.9);font-size:16px">Login Verification Code</p>
                        </td>
                    </tr>
                    
                    <!-- Body -->
                    <tr>
                        <td style="padding:40px 30px">
                            <p style="margin:0 0 20px;color:#333;font-size:16px;line-height:1.6">Hello,</p>
                            <p style="margin:0 0 30px;color:#666;font-size:15px;line-height:1.6">
                                You requested a one-time password to login to your account. Use the code below:
                            </p>
                            
                            <!-- OTP Code -->
                            <div style="background:#f8f9fa;border:2px dashed #667eea;border-radius:10px;padding:30px;text-align:center;margin:0 0 30px">
                                <div style="font-size:42px;font-weight:bold;color:#667eea;letter-spacing:8px;font-family:monospace">
                                    <?php echo esc_html($otp_code); ?>
                                </div>
                            </div>
                            
                            <p style="margin:0 0 20px;color:#666;font-size:14px;line-height:1.6">
                                This code will expire in <strong style="color:#333"><?php echo $expiry_minutes; ?> minutes</strong>.
                            </p>
                            
                            <div style="background:#fff3cd;border-left:4px solid #ffc107;padding:15px;margin:0 0 20px;border-radius:4px">
                                <p style="margin:0;color:#856404;font-size:14px">
                                    <strong>Security Note:</strong> If you didn't request this code, please ignore this email and ensure your account is secure.
                                </p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background:#f8f9fa;padding:30px;text-align:center;border-top:1px solid #e0e0e0">
                            <p style="margin:0 0 10px;color:#999;font-size:13px">
                                This is an automated message from <?php echo esc_html($site_name); ?>
                            </p>
                            <p style="margin:0;color:#ccc;font-size:12px">
                                &copy; <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
