<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Your sign-in code</title>
    <!--[if mso]>
    <style type="text/css">
        table, td { font-family: Arial, sans-serif !important; }
    </style>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f3f4f6; padding: 32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color: #c9092f; padding: 28px 32px; text-align: center;">
                            {{-- Embedded rather than linked: remote images are blocked by
                                 default in most clients, and this is an email the user has
                                 to be able to read at a glance. --}}
                            <img src="{{ $message->embed(public_path('img/logo.png')) }}" alt="Marshall's Lawn &amp; Landscape" style="height: 50px; margin-bottom: 8px; border: 0; outline: none; text-decoration: none;">
                            <p style="color: #ffffff; font-size: 14px; margin: 0; opacity: 0.9;">Field App Sign-in</p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 32px;">
                            <h1 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 16px;">Your sign-in code</h1>

                            <p style="font-size: 14px; line-height: 1.6; color: #374151; margin: 0 0 24px;">
                                Enter this code in the Marshall's Lawn app to finish signing in.
                                It expires in <strong style="color: #111827;">{{ $minutesValid }} minutes</strong>.
                            </p>

                            {{-- Code --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px;">
                                <tr>
                                    <td align="center" style="background-color: #fef1f3; border: 2px solid #e00a35; border-radius: 12px; padding: 22px 16px;">
                                        <div style="font-family: 'SF Mono', ui-monospace, Menlo, Consolas, 'Courier New', monospace; font-size: 38px; font-weight: 700; letter-spacing: 12px; color: #111827; line-height: 1;">
                                            {{ $code }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size: 13px; line-height: 1.6; color: #6b7280; margin: 0;">
                                If you didn't try to sign in, you can safely ignore this email — your
                                account is unaffected and the code will expire on its own.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding: 20px 32px; background: #f9fafb; border-top: 1px solid #e5e7eb; text-align: center;">
                            <p style="font-size: 12px; color: #9ca3af; margin: 0;">Marshall's Lawn &amp; Landscape</p>
                            <p style="font-size: 12px; color: #9ca3af; margin: 4px 0 0;">This email was sent from our management platform.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
