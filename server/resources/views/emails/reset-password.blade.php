<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 32px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color: #c9092f; padding: 28px 32px; text-align: center;">
                            <img src="{{ asset('img/logo.png') }}" alt="Marshall's Lawn & Landscape" style="height: 50px; margin-bottom: 8px;">
                            <p style="color: #ffffff; font-size: 14px; margin: 0; opacity: 0.9;">Password Reset</p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 32px;">
                            <h1 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 16px;">Reset your password</h1>

                            <p style="font-size: 14px; line-height: 1.6; color: #374151; margin: 0 0 16px;">
                                You are receiving this email because we received a password reset request for your account.
                                Click the button below to choose a new password.
                            </p>

                            {{-- CTA Button --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 24px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $url }}" style="display: inline-block; padding: 14px 40px; background-color: #c9092f; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 600; border-radius: 8px;">
                                            Reset Password
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size: 13px; line-height: 1.6; color: #6b7280; margin: 0 0 16px;">
                                This password reset link will expire in {{ $expireMinutes }} minutes. If you did not request a
                                password reset, no further action is required and you can safely ignore this email.
                            </p>

                            {{-- Fallback link --}}
                            <p style="font-size: 12px; line-height: 1.6; color: #9ca3af; margin: 24px 0 0; word-break: break-all;">
                                If you're having trouble clicking the "Reset Password" button, copy and paste this URL into your browser:<br>
                                <a href="{{ $url }}" style="color: #c9092f;">{{ $url }}</a>
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding: 20px 32px; background: #f9fafb; border-top: 1px solid #e5e7eb; text-align: center;">
                            <p style="font-size: 12px; color: #9ca3af; margin: 0;">Marshall's Lawn & Landscape</p>
                            <p style="font-size: 12px; color: #9ca3af; margin: 4px 0 0;">This email was sent from our management platform.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
