<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>{{ $settings['brand_name'] ?? 'Kiosk' }} — Temporarily Unavailable</title>

    <style>
        :root {
            color-scheme: light;
            --primary: {{ $settings['primary_color'] ?? '#bef264' }};
            --strong: {{ $settings['primary_strong_color'] ?? '#65a30d' }};
            --text: {{ $settings['primary_text_color'] ?? '#111111' }};
            --cream: #fff3c9;
            --ink: #111111;
            --muted: #716f67;
            --panel: #ffffff;
        }

        * { box-sizing: border-box; }

        html, body { min-height: 100%; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: clamp(18px, 4vw, 48px);
            background:
                radial-gradient(circle at 10% 15%, color-mix(in srgb, var(--primary) 32%, transparent) 0 12%, transparent 32%),
                radial-gradient(circle at 92% 88%, rgba(17,17,17,.08) 0 9%, transparent 25%),
                var(--cream);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .shell {
            width: min(920px, 100%);
            position: relative;
        }

        .panel {
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(180px, .55fr) 1.45fr;
            align-items: stretch;
            border: 3px solid var(--ink);
            border-radius: 34px;
            background: var(--panel);
            box-shadow: 12px 12px 0 var(--ink);
        }

        .visual {
            position: relative;
            display: flex;
            min-height: 430px;
            align-items: center;
            justify-content: center;
            padding: 42px;
            background: var(--ink);
            overflow: hidden;
        }

        .visual::before,
        .visual::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            border: 2px solid rgba(255,255,255,.08);
        }

        .visual::before {
            width: 260px;
            height: 260px;
        }

        .visual::after {
            width: 380px;
            height: 380px;
        }

        .logo-wrap {
            position: relative;
            z-index: 1;
            width: min(240px, 100%);
            min-height: 120px;
            display: grid;
            place-items: center;
            padding: 20px;
            border-radius: 26px;
            background: #fff;
            border: 2px solid var(--primary);
        }

        .logo {
            width: 100%;
            max-height: 92px;
            object-fit: contain;
        }

        .fallback-logo {
            color: #fff;
            font-size: 28px;
            line-height: .95;
            font-weight: 950;
            letter-spacing: -.04em;
            text-align: center;
        }

        .fallback-logo strong { color: var(--primary); }

        .content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: clamp(30px, 5vw, 58px);
        }

        .status {
            width: fit-content;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 9px 13px;
            border-radius: 999px;
            background: var(--primary);
            color: var(--text);
            font-size: 11px;
            font-weight: 950;
            letter-spacing: .14em;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--strong);
        }

        h1 {
            max-width: 580px;
            margin: 18px 0 0;
            font-size: clamp(42px, 6vw, 72px);
            line-height: .92;
            letter-spacing: -.045em;
            font-weight: 950;
        }

        .message {
            max-width: 560px;
            margin: 20px 0 0;
            color: var(--muted);
            font-size: clamp(16px, 2.1vw, 19px);
            line-height: 1.65;
            font-weight: 650;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 28px;
        }

        .button {
            display: inline-flex;
            min-height: 48px;
            align-items: center;
            justify-content: center;
            padding: 0 18px;
            border-radius: 15px;
            border: 2px solid var(--ink);
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
        }

        .button-primary {
            background: var(--ink);
            color: #fff;
        }

        .button-secondary {
            background: #fff;
            color: var(--ink);
        }

        .footer {
            margin-top: 24px;
            color: #9b988e;
            font-size: 12px;
            font-weight: 800;
        }

        @media (max-width: 760px) {
            .panel {
                grid-template-columns: 1fr;
            }

            .visual {
                min-height: 230px;
                padding: 28px;
            }

            .logo-wrap {
                width: min(230px, 78%);
            }

            .content {
                padding: 32px 26px 30px;
            }

            h1 {
                font-size: clamp(42px, 14vw, 62px);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * { scroll-behavior: auto !important; }
        }
    </style>
</head>

<body>
    <main class="shell">
        <section class="panel">
            <div class="visual" aria-hidden="true">
                <div class="logo-wrap">
                    @if(!empty($settings['logo_url']))
                        <img src="{{ $settings['logo_url'] }}" alt="" class="logo">
                    @else
                        <div class="fallback-logo">
                            <strong>{{ $settings['brand_short_name'] ?? 'STEP' }}</strong><br>
                            {{ $settings['brand_name'] ?? 'ORDER' }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="content">
                <span class="status">
                    <span class="status-dot"></span>
                    KIOSK TEMPORARILY UNAVAILABLE
                </span>

                <h1>We'll be right back.</h1>

                <p class="message">
                    {{ $settings['maintenance_message'] ?? 'The kiosk is temporarily unavailable. Please check back in a moment.' }}
                </p>

                <div class="actions">
                    <button class="button button-primary" type="button" onclick="window.location.reload()">
                        TRY AGAIN
                    </button>

                    <a class="button button-secondary" href="{{ url('/admin/login') }}">
                        STAFF ACCESS
                    </a>
                </div>

                <p class="footer">
                    {{ $settings['brand_tagline'] ?? 'Self-Service Footwear Kiosk' }}
                </p>
            </div>
        </section>
    </main>
</body>
</html>
