@php
    $settings = app(\App\Services\SettingsService::class)->all();
    $hasLogo = filled($settings['logo_url'] ?? null);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if(!empty($settings['favicon_url']))
        <link rel="icon" href="{{ $settings['favicon_url'] }}">
    @endif
    <title>{{ $title ?? $settings['brand_name'] }}</title>

    <script>
        window.STEPORDER_THEME = @json($settings['theme_mode']);
        window.STEPORDER_REDUCED_MOTION = @json($settings['reduced_motion']);
        window.STEPORDER_MAX_QTY = @json($settings['max_cart_quantity']);

        (function () {
            const mode = window.STEPORDER_THEME;
            const apply = () => {
                const theme = mode === 'system'
                    ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                    : mode;
                document.documentElement.dataset.theme = theme;
                if (window.STEPORDER_REDUCED_MOTION) document.documentElement.classList.add('reduce-motion');
            };
            apply();
            if (mode === 'system') {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', apply);
            }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --so-primary: {{ $settings['primary_color'] }};
            --so-primary-strong: {{ $settings['primary_strong_color'] }};
            --so-primary-text: {{ $settings['primary_text_color'] }};
        }

        * { scrollbar-width: thin; scrollbar-color: var(--so-primary-strong) #fff3c9; }
        *::-webkit-scrollbar { width: 10px; height: 10px; }
        *::-webkit-scrollbar-track { background: #fff3c9; border-radius: 999px; }
        *::-webkit-scrollbar-thumb { background: var(--so-primary-strong); border: 2px solid #fff3c9; border-radius: 999px; }
        *::-webkit-scrollbar-thumb:hover { background: var(--so-primary-strong); }

        .kiosk-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: var(--so-primary-strong) #fff3c9;
        }
        .kiosk-scrollbar::-webkit-scrollbar { width: 10px; height: 10px; }
        .kiosk-scrollbar::-webkit-scrollbar-track { background: #fff3c9; border-radius: 999px; }
        .kiosk-scrollbar::-webkit-scrollbar-thumb {
            background: var(--so-primary-strong);
            border: 2px solid #fff3c9;
            border-radius: 999px;
        }

        html[data-theme="dark"] {
            --so-page: #171717;
            --so-surface: #262626;
            --so-surface-soft: #1f1f1f;
            --so-surface-strong: #111111;
            --so-border: rgba(255,255,255,.18);
            --so-muted: #a8a29e;
            --so-on-primary: var(--so-primary-text);
        }
        html[data-theme="light"] {
            --so-page: #fff3c9;
            --so-surface: #ffffff;
            --so-surface-soft: #f5f5f4;
            --so-surface-strong: #111111;
            --so-border: rgba(17,17,17,.18);
            --so-muted: rgba(17,17,17,.52);
            --so-on-primary: var(--so-primary-text);
        }

        .bg-lime-300,
        .bg-lime-400 { background-color: var(--so-primary) !important; color: var(--so-primary-text) !important; }
        .text-lime-600 { color: var(--so-primary-strong) !important; }

        html[data-theme="dark"] body { background: var(--so-page) !important; color: #f5f5f4 !important; }
        html[data-theme="dark"] header { background-color: var(--so-page) !important; border-color: var(--so-border) !important; }
        html[data-theme="dark"] .bg-white { background-color: var(--so-surface) !important; }
        html[data-theme="dark"] [class*="bg-[#fff3c9]"] { background-color: var(--so-surface) !important; }
        html[data-theme="dark"] [class*="bg-[#d7e84e]"] { background-color: var(--so-primary) !important; color: var(--so-primary-text) !important; }
        html[data-theme="dark"] .bg-stone-50 { background-color: var(--so-surface-soft) !important; }
        html[data-theme="dark"] .bg-stone-100 { background-color: var(--so-surface-soft) !important; }
        html[data-theme="dark"] .bg-stone-200 { background-color: #292524 !important; }
        html[data-theme="dark"] .bg-black { background-color: #090909 !important; }
        html[data-theme="dark"] .text-black { color: #f5f5f4 !important; }
        html[data-theme="dark"] [class*="text-black/25"] { color: rgba(245,245,244,.28) !important; }
        html[data-theme="dark"] [class*="text-black/40"] { color: rgba(245,245,244,.48) !important; }
        html[data-theme="dark"] [class*="text-black/50"] { color: rgba(245,245,244,.56) !important; }
        html[data-theme="dark"] [class*="text-black/60"] { color: rgba(245,245,244,.66) !important; }
        html[data-theme="dark"] [class*="text-black/70"] { color: rgba(245,245,244,.76) !important; }
        html[data-theme="dark"] [class*="border-black/"] { border-color: var(--so-border) !important; }
        html[data-theme="dark"] .border-black { border-color: #f5f5f4 !important; }
        html[data-theme="dark"] input,
        html[data-theme="dark"] select,
        html[data-theme="dark"] textarea { background-color: var(--so-surface-soft) !important; color: #f5f5f4 !important; border-color: #57534e !important; }
        html[data-theme="dark"] ::placeholder { color: var(--so-muted) !important; }
        html[data-theme="dark"] option { background: #1f1f1f; color: #f5f5f4; }
        html[data-theme="dark"] .kiosk-scrollbar,
        html[data-theme="dark"] * { scrollbar-color: var(--so-primary-strong) #262626; }
        html[data-theme="dark"] *::-webkit-scrollbar-track { background: #262626; }
        html[data-theme="dark"] *::-webkit-scrollbar-thumb { border-color: #262626; }
        html[data-theme="dark"] .kiosk-product-card { color: var(--so-primary-text) !important; }
        html[data-theme="dark"] .kiosk-product-card .product-title,
        html[data-theme="dark"] .kiosk-product-card .product-price { color: var(--so-primary-text) !important; }
        html[data-theme="dark"] .kiosk-product-card .product-meta { color: rgba(17,17,17,.62) !important; }
        html[data-theme="dark"] .kiosk-cart-card { background: var(--so-surface) !important; color: #f5f5f4 !important; border-color: #f5f5f4 !important; }
        html[data-theme="dark"] .kiosk-cart-card .cart-muted { color: rgba(245,245,244,.58) !important; }
        html[data-theme="dark"] .kiosk-cart-card .cart-control { background: var(--so-surface-soft) !important; color: #f5f5f4 !important; border-color: #f5f5f4 !important; }
        html[data-theme="dark"] .kiosk-order-total { background: var(--so-primary) !important; color: var(--so-primary-text) !important; border-color: #f5f5f4 !important; }
        html[data-theme="dark"] .kiosk-modal { background: var(--so-surface) !important; color: #f5f5f4 !important; border-color: #f5f5f4 !important; }
        html[data-theme="dark"] .kiosk-modal-list { background: var(--so-surface-soft) !important; color: #f5f5f4 !important; border-color: #f5f5f4 !important; }

        html.reduce-motion *,
        html.reduce-motion *::before,
        html.reduce-motion *::after {
            animation-duration: .01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: .01ms !important;
            scroll-behavior: auto !important;
        }
    </style>
</head>
<body class="min-h-screen bg-[#fff3c9] text-black">
    <header class="sticky top-0 z-20 border-b-2 border-black bg-[#fff3c9]">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-5 py-4">
            <a href="{{ url('/') }}" class="flex items-center gap-3 text-3xl font-black" aria-label="{{ $settings['brand_name'] }}">
                @if($hasLogo)
                    <img src="{{ $settings['logo_url'] }}" alt="{{ $settings['brand_name'] }}" class="max-h-12 max-w-[190px] object-contain">
                @else
                    <span>{{ $settings['brand_name'] }}</span>
                @endif
            </a>
            <a href="{{ url('/cart') }}" class="rounded-full bg-lime-400 px-6 py-3 font-black">
                CART
                @if(count(session('cart', [])) > 0)
                    ({{ count(session('cart', [])) }})
                @endif
            </a>
        </div>
    </header>

    @if(session('error'))
        <div class="mx-auto mt-4 max-w-6xl rounded-xl bg-red-100 px-4 py-3 font-bold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="mx-auto mt-4 max-w-6xl rounded-xl bg-green-100 px-4 py-3 font-bold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @yield('content')

    <script>
        const currencySymbol = @json($settings['currency_symbol']);
        const currencyWalker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        let currencyNode;
        while (currencyNode = currencyWalker.nextNode()) {
            const parent = currencyNode.parentElement;
            if (!parent || ['SCRIPT', 'STYLE', 'INPUT', 'TEXTAREA'].includes(parent.tagName)) continue;
            if (currencyNode.nodeValue.includes('₱')) {
                currencyNode.nodeValue = currencyNode.nodeValue.replaceAll('₱', currencySymbol);
            }
        }

        @if($settings['idle_enabled'])
            const idleLimit = {{ (int)$settings['idle_seconds'] }} * 1000;
            let idleTimer;

            const resetIdleTimer = () => {
                clearTimeout(idleTimer);
                idleTimer = setTimeout(() => {
                    window.location.href = '{{ url('/') }}';
                }, idleLimit);
            };

            ['click','pointerdown','touchstart','keydown','scroll'].forEach(eventName => {
                window.addEventListener(eventName, resetIdleTimer, { passive: true });
            });

            resetIdleTimer();
        @endif
    </script>
</body>
</html>
