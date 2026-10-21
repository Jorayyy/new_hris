<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:0;background:#f4f8fc;font-family:Arial,Helvetica,sans-serif;color:#1b2436">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f8fc;padding:24px 12px">
    <tr>
        <td align="center">
            <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="max-width:480px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #dfe8f1">
                <tr>
                    <td style="background:#14181d;padding:20px 24px;text-align:center">
                        @if($logo)
                            <img src="{{ $logo }}" alt="{{ config('app.name') }}" style="max-height:64px;max-width:220px;background:#ffffff;padding:8px 12px;border-radius:8px">
                        @else
                            <span style="color:#ffffff;font-size:17px;font-weight:700">{{ config('app.name') }}</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px">
                        <p style="margin:0 0 8px;font-size:14px">Hi {{ $name }},</p>
                        <p style="margin:0 0 18px;font-size:14px;color:#4b5563">Use this code to finish signing in:</p>
                        <div style="font-size:32px;font-weight:700;letter-spacing:8px;text-align:center;background:#e7f3fb;border-radius:8px;padding:14px;color:#101418">{{ $code }}</div>
                        <p style="margin:18px 0 0;font-size:13px;color:#4b5563">It expires in {{ $ttl }} minutes. If you did not try to sign in, ignore this email.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:14px 24px;background:#f4f8fc;text-align:center;font-size:11px;color:#6b7280">{{ config('app.name') }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
