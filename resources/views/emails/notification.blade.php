{{--
    Branded BrgyHoops notification email (HTML).
    Data: $heading, $greeting, $lines (array), $details (label => value),
          $actionText, $actionUrl, $outro (array).
    Inline styles only — email clients ignore external CSS.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0;padding:0;background:#f5f6f8;font-family:'DM Sans',Arial,Helvetica,sans-serif;color:#111318;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f6f8;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:#111318;padding:22px 28px;border-right:8px solid #e21b23;">
                            <span style="font-family:'Barlow Condensed',Arial Narrow,Arial,sans-serif;font-size:28px;font-weight:900;letter-spacing:-.3px;color:#ffffff;text-transform:uppercase;">
                                Brgy<span style="color:#e21b23;">Hoops</span>
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:30px 28px 8px;">
                            <h1 style="margin:0 0 16px;font-family:'Barlow Condensed',Arial Narrow,Arial,sans-serif;font-size:28px;line-height:1.1;font-weight:800;text-transform:uppercase;color:#111318;">
                                {{ $heading }}
                            </h1>

                            <p style="margin:0 0 14px;font-size:15px;line-height:1.7;">{{ $greeting }}</p>

                            @foreach ($lines as $line)
                                <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#444955;">{{ $line }}</p>
                            @endforeach
                        </td>
                    </tr>

                    @if (!empty($details))
                        <tr>
                            <td style="padding:4px 28px 10px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f6f8;border-radius:10px;">
                                    @foreach ($details as $label => $value)
                                        <tr>
                                            <td style="padding:10px 16px;font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:#626976;width:38%;vertical-align:top;">{{ $label }}</td>
                                            <td style="padding:10px 16px;font-size:14px;font-weight:700;color:#111318;vertical-align:top;">{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @endif

                    @if (!empty($actionText) && !empty($actionUrl))
                        <tr>
                            <td style="padding:18px 28px 6px;">
                                <a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 22px;border-radius:9px;background:#e21b23;color:#ffffff;font-size:14px;font-weight:800;text-decoration:none;">
                                    {{ $actionText }} &rarr;
                                </a>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:18px 28px 28px;">
                            @foreach ($outro as $line)
                                <p style="margin:0 0 10px;font-size:13px;line-height:1.7;color:#626976;">{{ $line }}</p>
                            @endforeach

                            <p style="margin:14px 0 0;font-size:13px;color:#626976;">&mdash; The BrgyHoops Team</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#0b0c10;padding:16px 28px;font-size:12px;line-height:1.6;color:#81848e;">
                            BrgyHoops &middot; Inter-Barangay Basketball Tournament Management System.
                            You received this email because of activity on your BrgyHoops account.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
