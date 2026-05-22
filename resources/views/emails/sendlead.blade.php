<?php
/**
 * QuickDials — New Lead Notification Email
 *
 * Sent to the business owner (client) when a new enquiry arrives.
 *
 * Variables expected:
 *   $clientname  : Business owner's name (string)
 *   $lead        : object with the following nullable properties:
 *                    name, kw_text, city_name, email, mobile, code, remarks, created_at
 *
 * Tested in Gmail, Outlook 2019+, Apple Mail, Yahoo, mobile clients.
 * Container width: 600px (industry standard).
 */
 date_default_timezone_set('Asia/Kolkata');
// ── Safe helpers ────────────────────────────────────────────────
$e = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

$clientName  = !empty($clientname) ? $e($clientname) : 'there';
$leadName    = !empty($lead->name)      ? $e($lead->name)      : '';
$leadKw      = !empty($lead->kw_text)   ? $e($lead->kw_text)   : '';
$leadCity    = !empty($lead->city_name) ? $e($lead->city_name) : '';
$leadEmail   = !empty($lead->email)     ? $e($lead->email)     : '';
$leadMobile  = !empty($lead->mobile)    ? preg_replace('/[^0-9+]/', '', $lead->mobile) : '';
$leadCode    = !empty($lead->code)      ? preg_replace('/[^0-9+]/', '', $lead->code)   : '';
$leadFullPh  = $leadCode . $leadMobile; // for tel: / WhatsApp links
$leadPhDisp  = $e(($lead->code ?? '') . ($lead->mobile ?? ''));
$leadRemark  = !empty($lead->remarks) ? $e($lead->remarks)
              : (!empty($lead->remark) ? $e($lead->remark) : '');
$receivedAt  = date('d M Y, h:i A');

$brandRed    = '#EB2C3B';
$brandDark   = '#101828';
$brandMute   = '#475467';
$year        = date('Y');

// Build a preheader summary (shows in inbox preview)
$preheaderBits = array_filter([
    $leadName,
    $leadKw   ? 'looking for ' . $leadKw : '',
    $leadCity ? 'in ' . $leadCity : '',
]);
$preheader = $preheaderBits
    ? 'New lead: ' . implode(' · ', $preheaderBits)
    : 'You have received a new lead enquiry on QuickDials.';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <meta name="format-detection" content="telephone=no,date=no,address=no,email=no">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>New lead from QuickDials</title>

    <!--[if mso]>
    <style type="text/css">
        table, td, div, h1, p { font-family: Arial, sans-serif !important; }
    </style>
    <![endif]-->

    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; }
        table, td { mso-table-lspace:0pt; mso-table-rspace:0pt; }
        img { -ms-interpolation-mode:bicubic; border:0; outline:none; text-decoration:none; display:block; }
        body { margin:0 !important; padding:0 !important; width:100% !important; }
        a { color:#EB2C3B; }

        /* Mobile */
        @media screen and (max-width: 620px) {
            .email-container { width:100% !important; max-width:100% !important; }
            .px-mobile       { padding-left:20px !important; padding-right:20px !important; }
            .py-mobile       { padding-top:24px !important; padding-bottom:24px !important; }
            .stack           { display:block !important; width:100% !important; }
            .stack-pad       { padding:0 0 12px 0 !important; }
            .cta-stack       { display:block !important; width:100% !important; margin-bottom:10px !important; }
            .hide-mobile     { display:none !important; }
            .lead-name       { font-size:22px !important; line-height:28px !important; }
            .meta-block      { padding:16px !important; }
        }

        /* Dark mode (iOS Mail / Apple Mail) */
        @media (prefers-color-scheme: dark) {
            .bg-page    { background-color:#0f1115 !important; }
            .bg-card    { background-color:#1a1d23 !important; }
            .bg-soft    { background-color:#0f1115 !important; }
            .text-main  { color:#e6e8eb !important; }
            .text-muted { color:#9aa0a6 !important; }
            .divider    { border-color:#2a2f37 !important; }
            .pill-bg    { background-color:#2a1a1c !important; color:#ffb3ba !important; }
            .row-line   { border-color:#2a2f37 !important; }
        }
    </style>
</head>

<body class="bg-page" style="margin:0;padding:0;background-color:#f4f6fa;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

    <!-- Preheader (inbox preview) -->
    <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#f4f6fa;">
        <?php echo $e($preheader); ?>
        &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" class="bg-page" style="background-color:#f4f6fa;">
        <tr>
            <td align="center" style="padding:24px 12px;">

                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="email-container" style="width:600px;max-width:600px;">

                    <!-- Logo -->
                   <tr>
                        <td align="center" style="padding:0 0 20px 0;">
                            <a href="https://www.quickdials.com" target="_blank" style="text-decoration:none;">
                                <span style="font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:24px;font-weight:700;color:#2874F0;letter-spacing:-0.3px;">
                                    Quick<span style="color:#FB641B;">Dials</span>
                                </span>
                            </a>
                        </td>
                    </tr>

                    <!-- Card -->
                    <tr>
                        <td class="bg-card" style="background-color:#ffffff;border-radius:14px;box-shadow:0 1px 3px rgba(16,24,40,0.06),0 4px 16px rgba(16,24,40,0.04);overflow:hidden;">

                            <!-- Top accent strip + alert banner -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="height:4px;background-color:<?php echo $brandRed; ?>;line-height:4px;font-size:0;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td align="center" style="background-color:#fff4f5;padding:14px 20px;border-bottom:1px solid #fde2e4;">
                                        <span style="display:inline-block;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;line-height:18px;color:#9b1c1c;font-weight:700;letter-spacing:0.4px;text-transform:uppercase;">
                                            🔔 New Lead Received
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Greeting -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td class="px-mobile" style="padding:32px 40px 0 40px;">
                                        <p class="text-main" style="margin:0 0 4px 0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:15px;line-height:22px;color:<?php echo $brandDark; ?>;">
                                            Hi <strong><?php echo $clientName; ?></strong>,
                                        </p>
                                        <p class="text-muted" style="margin:0 0 24px 0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:22px;color:<?php echo $brandMute; ?>;">
                                            You've received a new customer enquiry through QuickDials. Respond quickly — leads contacted within <strong style="color:<?php echo $brandDark; ?>;">5 minutes</strong> convert <strong style="color:<?php echo $brandDark; ?>;">9× higher</strong>.
                                        </p>
                                    </td>
                                </tr>

                                <!-- Lead card -->
                                <tr>
                                    <td class="px-mobile" style="padding:0 40px 0 40px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" class="bg-soft meta-block" style="background-color:#f9fafb;border:1px solid #eaecf0;border-radius:12px;padding:22px;">

                                            <!-- Lead name + source pill -->
                                            <tr>
                                                <td style="padding:0 0 14px 0;">
                                                    <?php if ($leadName !== ''): ?>
                                                    <p class="lead-name text-main" style="margin:0 0 6px 10px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:20px;line-height:26px;color:<?php echo $brandDark; ?>;font-weight:700;">
                                                        <?php echo ucfirst($leadName); ?>
                                                    </p>
                                                    <?php endif; ?>

                                                    <?php if ($leadKw !== '' || $leadCity !== ''): ?>
                                                    <div style="margin-top:6px;">
                                                        <?php if ($leadKw !== ''): ?>
                                                        <span class="pill-bg" style="display:inline-block;background-color:#fff1f2;color:#28a745;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;font-weight:600;padding:5px 10px;border-radius:999px;margin-right:6px;">
                                                            🎯 <?php echo $leadKw; ?>
                                                        </span>
                                                        <?php endif; ?>
                                                        <?php if ($leadCity !== ''): ?>
                                                        <span class="pill-bg" style="display:inline-block;background-color:#fff1f2;color:#28a745;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;font-weight:600;padding:5px 10px;border-radius:999px;">
                                                            📍 <?php echo $leadCity; ?>
                                                        </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>

                                            <!-- Divider -->
                                            <tr>
                                                <td class="row-line" style="border-top:1px solid #eaecf0;font-size:0;line-height:0;height:1px;padding:0;">&nbsp;</td>
                                            </tr>

                                            <!-- Mobile row -->
                                            <?php if ($leadMobile !== ''): ?>
                                            <tr>
                                                <td style="padding:14px 0 14px 0;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                                        <tr>
                                                            <td width="110" valign="top" class="stack stack-pad" style="width:110px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;line-height:20px;color:<?php echo $brandMute; ?>;text-transform:uppercase;letter-spacing:0.6px;font-weight:600;">
                                                                📞 Mobile
                                                            </td>
                                                            <td valign="top" class="stack text-main" style="font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:15px;line-height:22px;color:<?php echo $brandDark; ?>;font-weight:600;">
                                                                <a href="tel:<?php echo $e($leadFullPh); ?>" style="color:<?php echo $brandDark; ?>;text-decoration:none;">
                                                                    <?php echo $leadPhDisp; ?>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr><td class="row-line" style="border-top:1px solid #eaecf0;font-size:0;line-height:0;height:1px;padding:0;">&nbsp;</td></tr>
                                            <?php endif; ?>

                                            <!-- Email row -->
                                            <?php if ($leadEmail !== ''): ?>
                                            <tr>
                                                <td style="padding:14px 0 14px 0;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                                        <tr>
                                                            <td width="110" valign="top" class="stack stack-pad" style="width:110px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;line-height:20px;color:<?php echo $brandMute; ?>;text-transform:uppercase;letter-spacing:0.6px;font-weight:600;">
                                                                ✉️ Email
                                                            </td>
                                                            <td valign="top" class="stack text-main" style="font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:15px;line-height:22px;color:<?php echo $brandDark; ?>;word-break:break-all;">
                                                                <a href="mailto:<?php echo $leadEmail; ?>" style="color:<?php echo $brandDark; ?>;text-decoration:none;font-weight:600;">
                                                                    <?php echo $leadEmail; ?>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr><td class="row-line" style="border-top:1px solid #eaecf0;font-size:0;line-height:0;height:1px;padding:0;">&nbsp;</td></tr>
                                            <?php endif; ?>

                                            <!-- Comment row -->
                                            <?php if ($leadRemark !== ''): ?>
                                            <tr>
                                                <td style="padding:14px 0 14px 0;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                                        <tr>
                                                            <td width="110" valign="top" class="stack stack-pad" style="width:110px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;line-height:20px;color:<?php echo $brandMute; ?>;text-transform:uppercase;letter-spacing:0.6px;font-weight:600;">
                                                                💬 Remarks
                                                            </td>
                                                            <td valign="top" class="stack text-main" style="font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:22px;color:<?php echo $brandDark; ?>;">
                                                                <?php echo nl2br($leadRemark); ?>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr><td class="row-line" style="border-top:1px solid #eaecf0;font-size:0;line-height:0;height:1px;padding:0;">&nbsp;</td></tr>
                                            <?php endif; ?>

                                            <!-- Received at -->
                                            <tr>
                                                <td style="padding:14px 0 0 0;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                                        <tr>
                                                            <td width="110" valign="top" class="stack stack-pad" style="width:110px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;line-height:20px;color:<?php echo $brandMute; ?>;text-transform:uppercase;letter-spacing:0.6px;font-weight:600;">
                                                            ⏱️ Received
                                                            </td>
                                                            <td valign="top" class="stack text-muted" style="font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:22px;color:<?php echo $brandMute; ?>;">
                                                                <?php echo $e($receivedAt); ?>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>

                                        </table>
                                    </td>
                                </tr>

                                <!-- CTA Buttons -->
                                <tr>
                                    <td class="px-mobile" style="padding:28px 40px 8px 40px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <?php if ($leadMobile !== ''): ?>
                                                <!-- Call -->
                                                <td class="cta-stack" valign="top" style="padding-right:6px;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                                        <tr>
                                                            <td align="center" style="background-color:<?php echo $brandRed; ?>;border-radius:8px;">
                                                                <a href="tel:<?php echo $e($leadFullPh); ?>" target="_blank" style="display:block;padding:13px 16px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;letter-spacing:0.3px;">
                                                                    📞 Call Now
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <!-- WhatsApp -->
                                                <td class="cta-stack" valign="top" style="padding-left:6px;padding-right:6px;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                                        <tr>
                                                            <td align="center" style="background-color:#25D366;border-radius:8px;">
                                                                <a href="https://wa.me/<?php echo $e(ltrim($leadFullPh, '+')); ?>?text=<?php echo rawurlencode('Hi ' . ($lead->name ?? '') . ', thank you for your enquiry on QuickDials. How can we help?'); ?>" target="_blank" style="display:block;padding:13px 16px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;letter-spacing:0.3px;">
                                                                    💬 WhatsApp
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <?php endif; ?>
                                                <?php if ($leadEmail !== ''): ?>
                                                <!-- Email -->
                                                <td class="cta-stack" valign="top" style="padding-left:6px;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                                        <tr>
                                                            <td align="center" style="background-color:#ffffff;border:1.5px solid #d0d5dd;border-radius:8px;">
                                                                <a href="mailto:<?php echo $leadEmail; ?>?subject=<?php echo rawurlencode('Re: Your enquiry on QuickDials'); ?>" target="_blank" style="display:block;padding:11.5px 16px;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;font-weight:700;color:<?php echo $brandDark; ?>;text-decoration:none;letter-spacing:0.3px;">
                                                                    ✉️ Email
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Pro tip -->
                                <tr>
                                    <td class="px-mobile" style="padding:24px 40px 0 40px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#fffaf2;border-left:3px solid #f59e0b;border-radius:6px;">
                                            <tr>
                                                <td style="padding:12px 14px;">
                                                    <p style="margin:0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;line-height:20px;color:#7a4a07;">
                                                        <strong>💡 Tip:</strong> Save this contact in your phone and follow up with a WhatsApp message — open rates are 5× higher than SMS or email.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Sign-off -->
                                <tr>
                                    <td class="px-mobile" style="padding:28px 40px 36px 40px;">
                                        <p class="text-muted" style="margin:0 0 4px 0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:22px;color:<?php echo $brandMute; ?>;">
                                            Best of luck with this lead!
                                        </p>
                                        <p class="text-muted" style="margin:0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:22px;color:<?php echo $brandMute; ?>;">
                                            <strong class="text-main" style="color:<?php echo $brandDark; ?>;">— The QuickDials Team</strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Help strip -->
                    <tr>
                        <td align="center" style="padding:20px 16px 4px 16px;">
                            <p class="text-muted" style="margin:0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;line-height:20px;color:#667085;">
                                Need help with your leads?
                                <a href="mailto:help@quickdials.com" style="color:<?php echo $brandRed; ?>;text-decoration:none;font-weight:600;">help@quickdials.com</a>
                                &nbsp;·&nbsp;
                                <a href="tel:+917559435943" style="color:<?php echo $brandRed; ?>;text-decoration:none;font-weight:600;">+91-75-5943-5943</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Legal footer -->
                    <tr>
                        <td align="center" style="padding:16px 16px 24px 16px;">
                            <p class="text-muted" style="margin:0 0 8px 0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:11px;line-height:16px;color:#98a2b3;">
                                © <?php echo $year; ?> <strong>QuickDials Pvt. Ltd.</strong><br>
                                203, Oxford Towers, 139, HAL Old Airport Rd, Kodihalli, Bengaluru, Karnataka 560008<br>
                                CIN: U63112KA2026PTC215594 · TAN: BLRQ01951F
                            </p>
                            <p class="text-muted" style="margin:0;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:11px;line-height:16px;color:#b0b6c0;">
                                This lead notification was sent because a customer enquired about your business on
                                <a href="https://www.quickdials.com" style="color:#b0b6c0;text-decoration:underline;">quickdials.com</a>.
                                This is an automated email — please do not reply.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>