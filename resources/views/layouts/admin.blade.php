@php
    $settings = app(\App\Services\SettingsService::class)->all();
    $currentStaff = session('steporder_admin', []);
    $isAdmin = ($currentStaff['role'] ?? '') === 'admin';
    $roleLabel = $isAdmin ? 'Administrator' : 'Cashier';
    $hasLogo = filled($settings['logo_url'] ?? null);
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

        :root { --so-sidebar-width: clamp(18rem, 22vw, 23rem); }
        .admin-sidebar { width: var(--so-sidebar-width); transition: transform .22s ease, width .22s ease; }
        .admin-shell { transition: padding-left .22s ease; }
        .admin-sidebar.is-collapsed { transform: translateX(-100%); }
        .sidebar-backdrop { transition: opacity .2s ease; }
        .sidebar-toggle .icon-close { display: none; }
        .sidebar-toggle.is-expanded .icon-menu { display: none; }
        .sidebar-toggle.is-expanded .icon-close { display: block; }
        .sidebar-toggle.is-collapsed .icon-menu { display: block; }
        .sidebar-toggle.is-collapsed .icon-close { display: none; }
        .nav-active { box-shadow: inset 4px 0 0 var(--so-primary-strong); background: color-mix(in srgb, var(--so-primary) 28%, white); }

        .bg-lime-300,
        .bg-lime-400 { background-color: var(--so-primary) !important; color: var(--so-primary-text) !important; }
        .text-lime-600 { color: var(--so-primary-strong) !important; }

        html[data-theme="dark"] body { background: #171717 !important; color: #f5f5f4 !important; }
        html[data-theme="dark"] .bg-white { background-color: #262626 !important; }
        html[data-theme="dark"] [class*="bg-[#fff3c9]"] { background-color: #262626 !important; }
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
        html[data-theme="dark"] [class*="bg-[#d7e84e]"] { background-color: var(--so-primary) !important; }

        html.reduce-motion *,
        html.reduce-motion *::before,
        html.reduce-motion *::after {
            animation-duration: .01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: .01ms !important;
            scroll-behavior: auto !important;
        }

        @media (prefers-reduced-motion: reduce) {
            .admin-sidebar,
            .admin-shell,
            .sidebar-backdrop { transition-duration: .01ms; }
        }

        @media (min-width: 1024px) {
            .admin-sidebar { transform: translateX(0); }
            .admin-sidebar.is-collapsed { transform: translateX(-100%); }
            .admin-shell { padding-left: var(--so-sidebar-width); }
            .admin-shell.is-collapsed { padding-left: 0; }
            .sidebar-backdrop { display: none !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-stone-100 text-black">
    <div id="mobile-overlay" class="sidebar-backdrop fixed inset-0 z-40 hidden bg-black/40 opacity-0"></div>

    <aside id="admin-sidebar"
           class="admin-sidebar fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-black/10 bg-white lg:translate-x-0">
        <div class="flex items-center justify-between border-b border-black/10 px-5 py-6">
            <a href="{{ url('/admin/dashboard') }}" class="flex min-w-0 flex-1 items-center justify-center gap-3 overflow-hidden">
                @if($hasLogo)
                    <img src="{{ $settings['logo_url'] }}" alt="{{ $settings['brand_name'] }}" class="block h-auto w-full max-w-[240px] object-contain">
                @else
                    <span class="min-w-0">
                        <span class="block truncate text-2xl font-black tracking-tight">{{ $settings['brand_name'] }}</span>
                        <span class="block truncate text-[10px] font-black uppercase tracking-[0.25em] text-black/40">{{ $settings['admin_label'] }}</span>
                    </span>
                @endif
            </a>
        </div>

        <div class="border-b border-black/10 px-5 py-4">
            <div class="rounded-2xl bg-[#fff3c9] p-4">
                <p class="text-xs font-black uppercase tracking-widest text-black/40">SIGNED IN</p>
                <p class="mt-1 truncate font-black">{{ session('steporder_admin.email', 'Administrator') }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs font-bold text-green-700">
                    <span class="h-2 w-2 rounded-full bg-green-500"></span>
                    {{ $roleLabel }} Access
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

            @if($isAdmin)
                <p class="mt-8 px-3 text-[10px] font-black uppercase tracking-[0.25em] text-black/30">Catalog</p>

                <div class="mt-2 space-y-1">
                    <a href="/admin/products"
                       class="block rounded-xl px-4 py-3 font-black {{ request()->is('admin/products*') ? 'nav-active' : 'hover:bg-stone-100' }}">
                        <span class="mr-2">◈</span> Products
                    </a>

                    <a href="{{ url('/admin/inventory') }}"
                       class="block rounded-xl px-4 py-3 font-black {{ request()->is('admin/inventory*') ? 'nav-active' : 'hover:bg-stone-100' }}">
                        <span class="mr-2">▤</span> Inventory
                    </a>

                    <a href="/admin/categories"
                       class="block rounded-xl px-4 py-3 font-black {{ request()->is('admin/categories*') ? 'nav-active' : 'hover:bg-stone-100' }}">
                        <span class="mr-2">◇</span> Categories
                    </a>
                </div>

                <p class="mt-8 px-3 text-[10px] font-black uppercase tracking-[0.25em] text-black/30">Management</p>

                <div class="mt-2 space-y-1">
                    <a href="{{ url('/admin/staff') }}"
                       class="block rounded-xl px-4 py-3 font-black {{ request()->is('admin/staff*') ? 'nav-active' : 'hover:bg-stone-100' }}">
                        <span class="mr-2">♙</span> Cashiers
                    </a>

                    <a href="{{ url('/admin/activity') }}"
                       class="block rounded-xl px-4 py-3 font-black {{ request()->is('admin/activity*') ? 'nav-active' : 'hover:bg-stone-100' }}">
                        <span class="mr-2">≋</span> Activity & Records
                    </a>
                </div>

                <p class="mt-8 px-3 text-[10px] font-black uppercase tracking-[0.25em] text-black/30">System</p>

                <div class="mt-2 space-y-1">
                    <a href="/admin/settings"
                       class="block rounded-xl px-4 py-3 font-black {{ request()->is('admin/settings*') ? 'nav-active' : 'hover:bg-stone-100' }}">
                        <span class="mr-2">⚙</span> Settings
                    </a>
                </div>
            @endif

            <div class="mt-8 space-y-1">
                <a href="{{ url('/') }}" target="_blank"
                   class="block rounded-xl px-4 py-3 font-black hover:bg-stone-100">
                    <span class="mr-2">↗</span> Open Kiosk
                </a>

                <form method="POST" action="/admin/logout">
                    @csrf
                    <button class="w-full rounded-xl px-4 py-3 text-left font-black text-red-600 hover:bg-red-50">
                        <span class="mr-2">↪</span> Sign Out
                    </button>
                </form>
            </div>
        </nav>

    </aside>

    <div id="admin-shell" class="admin-shell min-h-screen">
        <header class="sticky top-0 z-30 border-b border-black/10 bg-white/95 backdrop-blur">
            <div class="flex items-center justify-between px-4 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <button id="toggle-sidebar"
                            type="button"
                            aria-label="Collapse sidebar"
                            aria-expanded="true"
                            class="sidebar-toggle is-expanded grid h-12 w-12 place-items-center rounded-xl border-2 border-black bg-white"
                            title="Collapse sidebar">
                        <svg class="icon-menu h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                            <path d="M4 6h16"></path>
                            <path d="M4 12h16"></path>
                            <path d="M4 18h16"></path>
                        </svg>
                        <svg class="icon-close h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                            <path d="M6 6l12 12"></path>
                            <path d="M18 6L6 18"></path>
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <p class="font-black">{{ request()->is('admin/pos') ? 'Cashier / POS' : (request()->is('admin/settings*') ? 'Settings' : (request()->is('admin/activity*') ? 'Activity & Records' : (request()->is('admin/staff*') ? 'Cashiers' : ucfirst(last(explode('/', trim(request()->path(), '/'))) ?: 'Dashboard')))) }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="hidden rounded-full border border-black/10 bg-stone-50 px-3 py-2 text-xs font-black sm:inline-flex">{{ $roleLabel }}</span>
                    <a href="{{ url('/admin/pos') }}"
                       class="rounded-xl bg-black px-4 py-2 text-sm font-black text-white">
                        OPEN POS
                    </a>
                </div>
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
        const shell = document.getElementById('admin-shell');
        const overlay = document.getElementById('mobile-overlay');
        const toggleButton = document.getElementById('toggle-sidebar');

        function setSidebar(collapsed, persist = true) {
            sidebar.classList.toggle('is-collapsed', collapsed);
            shell.classList.toggle('is-collapsed', collapsed);
            overlay.classList.toggle('hidden', collapsed);
            overlay.classList.toggle('opacity-0', collapsed);
            overlay.classList.toggle('opacity-100', !collapsed);
            toggleButton?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            toggleButton?.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            toggleButton?.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            toggleButton?.classList.toggle('is-collapsed', collapsed);
            toggleButton?.classList.toggle('is-expanded', !collapsed);

            if (persist) {
                localStorage.setItem('steporder_admin_sidebar_collapsed', collapsed ? '1' : '0');
            }
        }

        const savedCollapsed = localStorage.getItem('steporder_admin_sidebar_collapsed') === '1';
        setSidebar(savedCollapsed, false);

        toggleButton?.addEventListener('click', () => {
            const collapsed = sidebar.classList.contains('is-collapsed');
            setSidebar(!collapsed);
        });

        overlay?.addEventListener('click', () => setSidebar(true));

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                overlay.classList.add('hidden');
            }
        });

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
