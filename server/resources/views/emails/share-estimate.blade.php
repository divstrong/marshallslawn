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
                            <p style="color: #ffffff; font-size: 14px; margin: 0; opacity: 0.9;">Estimate {{ $estimate->estimate_number }}</p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 32px;">
                            @if($personalMessage)
                                <div style="font-size: 14px; line-height: 1.6; color: #374151; margin-bottom: 24px; white-space: pre-line;">{{ $personalMessage }}</div>
                            @endif

                            {{-- Estimate summary card --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: #f9fafb; border-radius: 10px; border: 1px solid #e5e7eb; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="font-size: 13px; color: #6b7280; padding-bottom: 4px;">Estimate #</td>
                                                <td style="font-size: 13px; color: #111827; font-weight: 600; text-align: right; padding-bottom: 4px;">{{ $estimate->estimate_number }}</td>
                                            </tr>
                                            @if($estimate->valid_until)
                                            <tr>
                                                <td style="font-size: 13px; color: #6b7280; padding-bottom: 4px;">Valid Until</td>
                                                <td style="font-size: 13px; color: #111827; text-align: right; padding-bottom: 4px;">{{ $estimate->valid_until->format('M d, Y') }}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td colspan="2" style="padding: 12px 0 8px; border-top: 1px solid #e5e7eb;">
                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td style="font-size: 18px; font-weight: 700; color: #111827;">Total</td>
                                                            <td style="font-size: 22px; font-weight: 700; color: #c9092f; text-align: right;">${{ number_format($estimate->total, 2) }}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- CTA Button --}}
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $publicUrl }}" style="display: inline-block; padding: 14px 40px; background-color: #c9092f; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 600; border-radius: 8px;">
                                            View Full Estimate
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding: 20px 32px; background: #f9fafb; border-top: 1px solid #e5e7eb; text-align: center;">
                            <p style="font-size: 12px; color: #9ca3af; margin: 0;">Marshall's Lawn & Landscape</p>
                            <p style="font-size: 12px; color: #9ca3af; margin: 4px 0 0;">This estimate was sent from our management platform.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
