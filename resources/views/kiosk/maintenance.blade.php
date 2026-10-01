<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>{{ $settings['brand_name'] ?? 'Kiosk' }} — Temporarily Unavailable</title>
    <style>
        :root{color-scheme:light;--accent:{{ $settings['primary_color'] ?? '#bef264' }};--strong:{{ $settings['primary_strong_color'] ?? '#65a30d' }}}
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#fff3c9;color:#111;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
        .card{width:min(680px,100%);padding:44px;border:3px solid #111;border-radius:32px;background:#fff;box-shadow:10px 10px 0 #111;text-align:center}
        .logo{max-width:180px;max-height:72px;object-fit:contain;margin:0 auto 24px}
        .fallback{font-size:30px;font-weight:900;margin-bottom:24px}
        .pill{display:inline-block;padding:8px 14px;border-radius:999px;background:var(--accent);font-size:12px;font-weight:900;letter-spacing:.12em}
        h1{font-size:clamp(34px,6vw,58px);line-height:.95;margin:18px 0 12px;font-weight:900}
        p{font-size:17px;line-height:1.6;color:#555;font-weight:700;margin:0 auto;max-width:520px}
        .footer{margin-top:28px;font-size:12px;color:#888;font-weight:800}
    </style>
</head>
<body>
    <main class="card">
        @if(!empty($settings['logo_url']))
            <img src="{{ $settings['logo_url'] }}" class="logo" alt="{{ $settings['brand_name'] ?? 'Brand' }}">
        @else
            <div class="fallback"><span style="color:var(--strong)">{{ $settings['brand_short_name'] ?? 'STEP' }}</span>{{ ($settings['brand_name'] ?? 'ORDER') !== ($settings['brand_short_name'] ?? '') ? 'ORDER' : '' }}</div>
        @endif

        <span class="pill">KIOSK TEMPORARILY UNAVAILABLE</span>
        <h1>We'll be right back.</h1>
        <p>{{ $settings['maintenance_message'] ?? 'The kiosk is temporarily unavailable. Please check back in a moment.' }}</p>
        <div class="footer">{{ $settings['brand_tagline'] ?? 'Self-Service Footwear Kiosk' }}</div>
    </main>
</body>
</html>
