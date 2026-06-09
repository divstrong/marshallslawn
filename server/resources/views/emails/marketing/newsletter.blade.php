@php
    /** @var array $content */
    $headline = $content['headline'] ?? '';
    $body = $content['body'] ?? '';
    $imageUrl = $content['image_url'] ?? null;
    $buttonLabel = $content['button_label'] ?? null;
    $buttonUrl = $content['button_url'] ?? null;
    $footerNote = $content['footer_note'] ?? '';
    $brand = '#e00a35';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $headline }}</title>
</head>
<body style="margin:0; padding:0; background:#f3f4f6; font-family:Georgia,'Times New Roman',serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background:#ffffff; border:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:20px 40px; border-bottom:3px solid {{ $brand }};">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
                                <td style="vertical-align:middle;">
                                    <img src="https://app.marshallslawninc.com/img/logo.png" alt="Marshall's Lawn &amp; Landscape" width="150" style="display:block; max-width:150px; height:auto;">
                                </td>
                                <td style="vertical-align:middle; text-align:right; font-family:Arial,Helvetica,sans-serif; font-size:12px; letter-spacing:1px; text-transform:uppercase; color:#9ca3af;">Newsletter</td>
                            </tr></table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 40px 8px;">
                            <h1 style="margin:0; font-size:28px; line-height:1.3; color:#111827;">{{ $headline }}</h1>
                        </td>
                    </tr>
                    @if ($imageUrl)
                        <tr><td style="padding:16px 40px 0;"><img src="{{ $imageUrl }}" alt="" width="520" style="display:block; width:100%; height:auto; border-radius:6px;"></td></tr>
                    @endif
                    <tr>
                        <td style="padding:16px 40px 24px; font-size:16px; line-height:1.7; color:#374151;">
                            {!! nl2br(e($body)) !!}
                        </td>
                    </tr>
                    @if ($buttonUrl && $buttonLabel)
                        <tr>
                            <td style="padding:0 40px 36px;">
                                <a href="{{ $buttonUrl }}" style="display:inline-block; background:{{ $brand }}; color:#ffffff; text-decoration:none; font-family:Arial,Helvetica,sans-serif; font-weight:bold; font-size:15px; padding:13px 28px; border-radius:6px;">{{ $buttonLabel }}</a>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:20px 40px; background:#111827; font-family:Arial,Helvetica,sans-serif; font-size:12px; line-height:1.6; color:#9ca3af;">
                            {!! nl2br(e($footerNote)) !!}
                            <div style="margin-top:8px; color:#d1d5db;">Marshall's Lawn &amp; Landscape</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
