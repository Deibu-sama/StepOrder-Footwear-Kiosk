@php
    $settings = app(\App\Services\SettingsService::class)->all();
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    @if(!empty($settings['favicon_url']))
        <link rel="icon" href="{{ $settings['favicon_url'] }}">
    @endif
    <title>{{ $title ?? $settings['brand_name'].' Admin' }}</title>
    <script>
        window.STEPORDER_THEME = @json($settings['theme_mode']);
        window.STEPORDER_REDUCED_MOTION = @json($settings['reduced_motion']);

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

        * { scrollbar-width: thin; scrollbar-color: var(--so-primary-strong) #f5f5f4; }
        *::-webkit-scrollbar { width: 10px; height: 10px; }
        *::-webkit-scrollbar-track { background: #f5f5f4; border-radius: 999px; }
        *::-webkit-scrollbar-thumb { background: var(--so-primary-strong); border: 2px solid #f5f5f4; border-radius: 999px; }

        .admin-sidebar { transition: transform .2s ease; }
        .nav-active { box-shadow: inset 4px 0 0 var(--so-primary-strong); background: color-mix(in srgb, var(--so-primary) 28%, white); }

        .bg-lime-300,
        .bg-lime-400 { background-color: var(--so-primary) !important; color: var(--so-primary-text) !important; }
        .text-lime-600 { color: var(--so-primary-strong) !important; }

        html[data-theme="dark"] body { background: #171717 !important; color: #f5f5f4 !important; }
        html[data-theme="dark"] .bg-white { background-color: #262626 !important; }
        html[data-theme="dark"] .bg-stone-50 { background-color: #1c1917 !important; }
        html[data-theme="dark"] .bg-stone-100 { background-color: #1c1917 !important; }
        html[data-theme="dark"] .bg-black { background-color: #090909 !important; }
        html[data-theme="dark"] [class*="text-black/"] { color: rgba(245,245,244,.5) !important; }
        html[data-theme="dark"] .text-black { color: #f5f5f4 !important; }
        html[data-theme="dark"] [class*="border-black/"] { border-color: rgba(255,255,255,.12) !important; }
        html[data-theme="dark"] .border-black { border-color: #f5f5f4 !important; }
        html[data-theme="dark"] input,
        html[data-theme="dark"] select,
        html[data-theme="dark"] textarea { background-color: #1c1917 !important; color: #f5f5f4 !important; border-color: #57534e !important; }
        html[data-theme="dark"] ::placeholder { color: #a8a29e !important; }
        html[data-theme="dark"] .nav-active { background: rgba(190,242,100,.12); }

        html.reduce-motion *,
        html.reduce-motion *::before,
        html.reduce-motion *::after {
            animation-duration: .01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: .01ms !important;
            scroll-behavior: auto !important;
        }

        @media (prefers-reduced-motion: reduce) {
            .admin-sidebar { transition-duration: .01ms; }
        }
    </style>
</head>
<body class="min-h-screen bg-stone-100 text-black">
    <div id="mobile-overlay" class="fixed inset-0 z-40 hidden bg-black/40 lg:hidden"></div>

    <aside id="admin-sidebar"
           class="admin-sidebar fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-black/10 bg-white lg:translate-x-0">
        <div class="flex items-center justify-between border-b border-black/10 px-5 py-5">
            <a href="{{ url('/admin/dashboard') }}" class="flex min-w-0 items-center gap-3">
                @if(!empty($settings['logo_url']))
                    <img src="{{ $settings['logo_url'] }}" alt="{{ $settings['brand_name'] }}" class="max-h-10 max-w-28 object-contain">
                @endif
                <span class="min-w-0">
                    <span class="block truncate text-2xl font-black tracking-tight">{{ $settings['brand_name'] }}</span>
                    <span class="block truncate text-[10px] font-black uppercase tracking-[0.25em] text-black/40">{{ $settings['admin_label'] }}</span>
                </span>
            </a>
            <button id="close-sidebar" class="rounded-xl border-2 border-black px-3 py-2 font-black lg:hidden">×</button>
        </div>

        <div class="border-b border-black/10 px-5 py-4">
            <div class="rounded-2xl bg-[#fff3c9] p-4">
                <p class="text-xs font-black uppercase tracking-widest text-black/40">SIGNED IN</p>
                <p class="mt-1 truncate font-black">{{ session('steporder_admin.email', 'Administrator') }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs font-bold text-green-700">
                    <span class="h-2 w-2 rounded-full bg-green-500"></span>
                    Admin / Cashier Access
                </div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto p-4">
            <p class="px-3 text-[10px] font-black uppercase tracking-[0.25em] text-black/30">Workspace</p>

            <div class="mt-2 space-y-1">
                <a href="{{ url('/admin/dashboard') }}"
                   class="block rounded-xl px-4 py-3 font-black {{ request()->is('admin/dashboard') ? 'nav-active' : 'hover:bg-stone-100' }}">
                    <span class="mr-2">▦</span> Dashboard
                </a>

                <a href="{{ url('/admin/pos') }}"
                   class="block rounded-xl px-4 py-3 font-black {{ request()->is('admin/pos') ? 'nav-active' : 'hover:bg-stone-100' }}">
                    <span class="mr-2">▣</span> Cashier / POS
                </a>

                <a href="{{ url('/admin/orders') }}"
                   class="block rounded-xl px-4 py-3 font-black {{ request()->is('admin/orders*') ? 'nav-active' : 'hover:bg-stone-100' }}">
                    <span class="mr-2">☷</span> Orders
                </a>
            </div>

            <p class="mt-8 px-3 text-[10px] font-black uppercase tracking-[0.25em] text-black/30">Catalog</p>

            <div class="mt-2 space-y-1">
                <a href="{{ url('/admin/products') }}"
                   class="block rounded-xl px-4 py-3 font-black {{ request()->is('admin/products*') ? 'nav-active' : 'hover:bg-stone-100' }}">
                    <span class="mr-2">◈</span> Products
                </a>

                <a href="{{ url('/admin/inventory') }}"
                   class="block rounded-xl px-4 py-3 font-black {{ request()->is('admin/inventory*') ? 'nav-active' : 'hover:bg-stone-100' }}">
                    <span class="mr-2">▤</span> Inventory
                </a>

                <a href="{{ url('/admin/categories') }}"
                   class="block rounded-xl px-4 py-3 font-black {{ request()->is('admin/categories*') ? 'nav-active' : 'hover:bg-stone-100' }}">
                    <span class="mr-2">◇</span> Categories
                </a>
            </div>

            <p class="mt-8 px-3 text-[10px] font-black uppercase tracking-[0.25em] text-black/30">System</p>

            <div class="mt-2 space-y-1">
                <a href="{{ url('/admin/settings') }}"
                   class="block rounded-xl px-4 py-3 font-black {{ request()->is('admin/settings*') ? 'nav-active' : 'hover:bg-stone-100' }}">
                    <span class="mr-2">⚙</span> Settings
                </a>

                <a href="{{ url('/') }}" target="_blank"
                   class="block rounded-xl px-4 py-3 font-black hover:bg-stone-100">
                    <span class="mr-2">↗</span> Open Kiosk
                </a>

                <form method="POST" action="{{ url('/admin/logout') }}">
                    @csrf
                    <button class="w-full rounded-xl px-4 py-3 text-left font-black text-red-600 hover:bg-red-50">
                        <span class="mr-2">↪</span> Sign Out
                    </button>
                </form>
            </div>
        </nav>

        <div class="border-t border-black/10 p-4">
            <div class="rounded-xl border border-black/10 bg-stone-50 p-3 text-xs font-bold text-black/50">
                {{ $settings['brand_name'] }}
                <div class="mt-1">{{ $settings['brand_tagline'] }}</div>
            </div>
        </div>
    </aside>

    <div class="min-h-screen lg:pl-72">
        <header class="sticky top-0 z-30 border-b border-black/10 bg-white/95 backdrop-blur">
            <div class="flex items-center justify-between px-4 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <button id="open-sidebar" class="rounded-xl border-2 border-black px-3 py-2 font-black lg:hidden">☰</button>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.25em] text-black/40">{{ $settings['brand_short_name'] }}</p>
                        <p class="font-black">{{ request()->is('admin/pos') ? 'Cashier / POS' : (request()->is('admin/settings*') ? 'Settings' : ucfirst(last(explode('/', trim(request()->path(), '/'))) ?: 'Dashboard')) }}</p>
                    </div>
                </div>

                <a href="{{ url('/admin/pos') }}"
                   class="rounded-xl bg-black px-4 py-2 text-sm font-black text-white">
                    OPEN POS
                </a>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8">
            @if(session('success'))
                <div class="mb-5 flex items-start gap-3 rounded-2xl border-2 border-green-200 bg-green-50 p-4 font-bold text-green-700">
                    <span>✓</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 flex items-start gap-3 rounded-2xl border-2 border-red-200 bg-red-50 p-4 font-bold text-red-700">
                    <span>!</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 rounded-2xl border-2 border-red-200 bg-red-50 p-4 text-red-700">
                    <p class="font-black">Please check the following:</p>
                    <ul class="mt-2 list-disc pl-5 font-bold">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        const sidebar = document.getElementById('admin-sidebar');
        const overlay = document.getElementById('mobile-overlay');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }

        document.getElementById('open-sidebar')?.addEventListener('click', openSidebar);
        document.getElementById('close-sidebar')?.addEventListener('click', closeSidebar);
        overlay?.addEventListener('click', closeSidebar);

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
    </script>

    @stack('scripts')
</body>
</html>
