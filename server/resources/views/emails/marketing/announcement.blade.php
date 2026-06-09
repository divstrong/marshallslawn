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
<body style="margin:0; padding:0; background:#f3f4f6; font-family:Arial,Helvetica,sans-serif; color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background:#ffffff; border-radius:12px; overflow:hidden;">
                    <tr>
                        <td align="center" style="background:{{ $brand }}; padding:24px;">
                            <img src="https://app.marshallslawninc.com/img/logo.png" alt="Marshall's Lawn &amp; Landscape" width="200" style="display:block; max-width:200px; height:auto;">
                        </td>
                    </tr>
                    @if ($imageUrl)
                        <tr><td><img src="{{ $imageUrl }}" alt="" width="600" style="display:block; width:100%; height:auto;"></td></tr>
                    @endif
                    <tr>
                        <td style="padding:32px 40px 8px;">
                            <h1 style="margin:0; font-size:26px; line-height:1.25; color:#111827; text-align:center;">{{ $headline }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 40px 24px; font-size:16px; line-height:1.6; color:#374151; text-align:center;">
                            {!! nl2br(e($body)) !!}
                        </td>
                    </tr>
                    @if ($buttonUrl && $buttonLabel)
                        <tr>
                            <td align="center" style="padding:0 40px 36px;">
                                <a href="{{ $buttonUrl }}" style="display:inline-block; background:{{ $brand }}; color:#ffffff; text-decoration:none; font-weight:bold; font-size:16px; padding:14px 32px; border-radius:8px;">{{ $buttonLabel }}</a>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:20px 40px; background:#f9fafb; border-top:1px solid #e5e7eb; font-size:12px; line-height:1.6; color:#9ca3af; text-align:center;">
                            {!! nl2br(e($footerNote)) !!}
                            <div style="margin-top:8px;">Marshall's Lawn &amp; Landscape</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
