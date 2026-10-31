<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:0;background:#F7F7F5;font-family:Arial,Helvetica,sans-serif;color:#2E3A2F;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7F7F5;padding:24px 12px;">
    <tr><td align="center">
        <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #E8EFE6;">
            <tr><td style="background:#3E5F46;padding:18px 24px;border-bottom:3px solid #D9C9B6;">
                <span style="font-family:Georgia,serif;font-size:20px;color:#ffffff;">{{ config('app.name') }}</span>
            </td></tr>
            <tr><td style="padding:26px 24px 8px;">
                <h1 style="margin:0 0 14px;font-family:Georgia,serif;font-size:21px;line-height:1.3;color:#2E3A2F;">{{ $headline }}</h1>
                @foreach ($lines as $line)
                    <p style="margin:0 0 8px;font-size:15px;line-height:1.55;color:#44524A;">{{ $line }}</p>
                @endforeach
            </td></tr>
            @if ($url)
                <tr><td style="padding:14px 24px 26px;">
                    <a href="{{ $url }}" style="display:inline-block;background:#24402D;color:#ffffff;text-decoration:none;padding:11px 24px;border-radius:999px;font-size:14px;font-weight:bold;">{{ $button }}</a>
                </td></tr>
            @endif
            <tr><td style="background:#E8EFE6;padding:14px 24px;font-size:12px;color:#64756A;">
                This is an automatic message from your shop's admin panel.
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>