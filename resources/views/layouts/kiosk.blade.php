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

        .bg-lime-300,
        .bg-lime-400 { background-color: var(--so-primary) !important; color: var(--so-primary-text) !important; }
        .text-lime-600 { color: var(--so-primary-strong) !important; }

        html[data-theme="dark"] body { background: #171717 !important; color: #f5f5f4 !important; }
        html[data-theme="dark"] .bg-white { background-color: #262626 !important; }
        html[data-theme="dark"] [class*="bg-[#fff3c9]"] { background-color: #262626 !important; }
        html[data-theme="dark"] [class*="bg-[#d7e84e]"] { background-color: var(--so-primary) !important; }
        html[data-theme="dark"] .bg-stone-50 { background-color: #1c1917 !important; }
        html[data-theme="dark"] .bg-stone-100 { background-color: #1c1917 !important; }
        html[data-theme="dark"] .bg-stone-200 { background-color: #292524 !important; }
        html[data-theme="dark"] .text-black { color: #f5f5f4 !important; }
        html[data-theme="dark"] [class*="text-black/"] { color: rgba(245,245,244,.55) !important; }
        html[data-theme="dark"] [class*="border-black/"] { border-color: rgba(255,255,255,.14) !important; }
        html[data-theme="dark"] .border-black { border-color: #f5f5f4 !important; }
        html[data-theme="dark"] input,
        html[data-theme="dark"] select,
        html[data-theme="dark"] textarea { background-color: #1c1917 !important; color: #f5f5f4 !important; border-color: #57534e !important; }
        html[data-theme="dark"] ::placeholder { color: #a8a29e !important; }

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
