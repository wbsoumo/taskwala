<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Taskwala Verification Code' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 40px 10px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 540px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08); border: 1px solid #e2e8f0;">
                    <!-- Brand Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); padding: 32px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 26px; font-weight: 800; letter-spacing: -0.5px;">
                                Taskwala
                            </h1>
                            <p style="margin: 6px 0 0 0; color: #c7d2fe; font-size: 13px; font-weight: 500;">
                                Partner Affiliate & Monetization Network
                            </p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 36px 32px 28px 32px;">
                            <h2 style="margin: 0 0 12px 0; color: #0f172a; font-size: 20px; font-weight: 700;">
                                {{ $heading ?? 'Verification Code' }}
                            </h2>
                            <p style="margin: 0 0 20px 0; color: #475569; font-size: 14px; line-height: 1.6;">
                                Hello {{ $name ?? 'Partner' }},
                            </p>
                            <p style="margin: 0 0 24px 0; color: #475569; font-size: 14px; line-height: 1.6;">
                                {{ $bodyText ?? 'Please use the 6-digit verification code below to proceed:' }}
                            </p>

                            <!-- OTP Code Box -->
                            <div style="background-color: #f8fafc; border: 2px dashed #4f46e5; border-radius: 12px; padding: 20px; text-align: center; margin: 24px 0;">
                                <div style="font-family: 'Courier New', Courier, monospace; font-size: 36px; font-weight: 800; letter-spacing: 12px; color: #4f46e5; padding-left: 12px;">
                                    {{ $otp }}
                                </div>
                            </div>

                            <p style="margin: 0 0 8px 0; color: #64748b; font-size: 13px; text-align: center;">
                                ⏱️ This code will expire in <strong>15 minutes</strong>.
                            </p>
                            <p style="margin: 0; color: #94a3b8; font-size: 12px; text-align: center; line-height: 1.5;">
                                If you did not request this verification code, please ignore this email or reach out to <a href="mailto:hello@taskwala.co.in" style="color: #4f46e5; text-decoration: none;">hello@taskwala.co.in</a>.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 30px; text-align: center;">
                            <p style="margin: 0; color: #94a3b8; font-size: 12px;">
                                &copy; 2026 Taskwala. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
