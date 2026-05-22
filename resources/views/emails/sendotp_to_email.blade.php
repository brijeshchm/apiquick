<?php
/**
 * QuickDials — OTP / Login Verification Email
 *
 * Variables expected:
 *   $msg  : The OTP code (string|int)
 *   $name : Optional recipient name (string) — falls back to "there"
 *
 * Tested in Gmail, Outlook 2019+, Apple Mail, Yahoo, Thunderbird, mobile clients.
 * Width: 600px (industry-standard, safe for all clients).
 */

$otp        = isset($otp)  ? trim((string) $otp)  : '';
$name       = isset($name) ? trim((string) $name) : '';
$greetName  = $name !== '' ? htmlspecialchars($name, ENT_QUOTES, 'UTF-8') : 'there';
$brandName  = 'QuickDials';
$brandColor = '#2874F0';      // primary
$accentColor = '#FB641B';     // accent
$year       = date('Y');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>Your <?php echo $brandName; ?> verification code</title>

    <!--[if mso]>
    <style type="text/css">
        table, td, div, h1, p { font-family: Arial, sans-serif !important; }
    </style>
    <![endif]-->

    <style type="text/css">
        /* Reset */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; }

        /* Mobile */
        @media screen and (max-width: 620px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .px-mobile { padding-left: 24px !important; padding-right: 24px !important; }
            .otp-code  { font-size: 32px !important; letter-spacing: 8px !important; }
            .hide-mobile { display: none !important; }
            .stack { display: block !important; width: 100% !important; }
        }

        /* Dark mode (Apple Mail, iOS Mail) */
        @media (prefers-color-scheme: dark) {
            .bg-page    { background-color: #0f1115 !important; }
            .bg-card    { background-color: #1a1d23 !important; }
            .text-main  { color: #e6e8eb !important; }
            .text-muted { color: #9aa0a6 !important; }
            .otp-box    { background-color: #0f1115 !important; border-color: #2a2f37 !important; }
            .divider    { border-color: #2a2f37 !important; }
        }
    </style>
</head>

<body class="bg-page" style="margin:0;padding:0;background-color:#f4f6fa;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

    <!-- Preheader (hidden preview text shown by Gmail/Apple Mail in the inbox list) -->
    <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#f4f6fa;">
        Your <?php echo $brandName; ?> login code is <?php echo htmlspecialchars($otp, ENT_QUOTES, 'UTF-8'); ?>. Valid for 10 minutes.
        &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" class="bg-page" style="background-color:#f4f6fa;">
        <tr>
            <td align="center" style="padding:32px 16px;">

                <!-- Email container -->
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="email-container" style="width:600px;max-width:600px;">

                    <!-- Brand bar -->
                    <tr>
                        <td align="center" style="padding:0 0 20px 0;">
                            <a href="https://www.quickdials.com" target="_blank" style="text-decoration:none;">
                                <span style="font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:24px;font-weight:700;color:<?php echo $brandColor; ?>;letter-spacing:-0.3px;">
                                    Quick<span style="color:<?php echo $accentColor; ?>;">Dials</span>
                                </span>
                            </a>
                        </td>
                    </tr>

                    <!-- Card -->
                    <tr>
                        <td class="bg-card" style="background-color:#ffffff;border-radius:14px;box-shadow:0 1px 3px rgba(16,24,40,0.06),0 4px 16px rgba(16,24,40,0.04);overflow:hidden;">

                            <!-- Top accent bar -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="height:4px;background:linear-gradient(90deg,<?php echo $brandColor; ?> 0%,<?php echo $accentColor; ?> 100%);line-height:4px;font-size:0;">&nbsp;</td>
                                </tr>
                            </table>

                            <!-- Body -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td class="px-mobile" style="padding:40px 48px 16px 48px;">

                                        <h1 class="text-main" style="margin:0 0 8px 0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:24px;line-height:32px;color:#101828;font-weight:700;">
                                            Verify your login
                                        </h1>

                                        <p class="text-muted" style="margin:0 0 28px 0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:15px;line-height:24px;color:#475467;">
                                            Hi <?php echo $greetName; ?>, use the one-time code below to securely sign in to your <?php echo $brandName; ?> account.
                                        </p>

                                    </td>
                                </tr>

                                <!-- OTP block -->
                                <tr>
                                    <td class="px-mobile" align="center" style="padding:0 48px 8px 48px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <td class="otp-box" align="center" style="background-color:#f4f6fa;border:1px dashed #d0d5dd;border-radius:12px;padding:24px 16px;">
                                                    <div class="text-muted" style="font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;line-height:16px;color:#667085;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;margin:0 0 10px 0;">
                                                        Your Login OTP
                                                    </div>
                                                    <div class="otp-code text-main" style="font-family:'Courier New','Consolas',monospace;font-size:40px;line-height:48px;color:#101828;font-weight:700;letter-spacing:12px;padding-left:12px;">
                                                        <?php echo htmlspecialchars($otp, ENT_QUOTES, 'UTF-8'); ?>
                                                    </div>
                                                    <div class="text-muted" style="font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;line-height:20px;color:#667085;margin:14px 0 0 0;">
                                                        Valid for <strong style="color:#101828;">10 minutes</strong>. Do not share this code.
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Security note -->
                                <tr>
                                    <td class="px-mobile" style="padding:28px 48px 0 48px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#fff8f1;border-left:3px solid <?php echo $accentColor; ?>;border-radius:6px;">
                                            <tr>
                                                <td style="padding:14px 16px;">
                                                    <p style="margin:0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;line-height:20px;color:#5a3a1a;">
                                                        <strong>Didn't request this?</strong> You can safely ignore this email. Someone may have typed your email address by mistake. Your account is still secure.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Divider -->
                                <tr>
                                    <td class="px-mobile" style="padding:32px 48px 0 48px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr><td class="divider" style="border-top:1px solid #e4e7ec;font-size:0;line-height:0;height:1px;">&nbsp;</td></tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Sign-off -->
                                <tr>
                                    <td class="px-mobile" style="padding:24px 48px 40px 48px;">
                                        <p class="text-muted" style="margin:0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:22px;color:#475467;">
                                            Thanks,<br>
                                            <strong class="text-main" style="color:#101828;">The <?php echo $brandName; ?> Team</strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding:24px 16px 8px 16px;">
                            <p class="text-muted" style="margin:0 0 8px 0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;line-height:18px;color:#98a2b3;">
                                Need help? Reach us at
                                <a href="mailto:support@quickdials.com" style="color:<?php echo $brandColor; ?>;text-decoration:none;font-weight:600;">support@quickdials.com</a>
                            </p>
                            <p class="text-muted" style="margin:0 0 12px 0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;line-height:18px;color:#98a2b3;">
                                © <?php echo $year; ?> <?php echo $brandName; ?>. All rights reserved.
                            </p>
                            <p class="text-muted" style="margin:0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:11px;line-height:16px;color:#b0b6c0;">
                                This is an automated message, please do not reply directly to this email.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- /Email container -->

            </td>
        </tr>
    </table>

</body>
</html>
