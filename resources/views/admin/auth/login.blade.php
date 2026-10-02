@php($settings = app(\App\Services\SettingsService::class)->all())
<!doctype html>
<html lang="en">
<head>
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    @if(!empty($settings['favicon_url']))
        <link rel="icon" href="{{ $settings['favicon_url'] }}">
    @endif
    <title>{{ $settings['brand_name'] }} Admin</title>
    <script>
        const themeMode = @json($settings['theme_mode']);
        const applyTheme = () => {
            document.documentElement.dataset.theme = themeMode === 'system'
                ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : themeMode;
        };
        applyTheme();
        if (themeMode === 'system') {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', applyTheme);
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root{--so-primary:{{ $settings['primary_color'] }};--so-strong:{{ $settings['primary_strong_color'] }}}
        .bg-lime-300,.bg-lime-400{background:var(--so-primary)!important;color:{{ $settings['primary_text_color'] }}!important}
        .text-lime-400,.text-lime-600{color:var(--so-strong)!important}
        html[data-theme="dark"] body{background:#171717!important;color:#f5f5f4}
        html[data-theme="dark"] .bg-white{background:#262626!important}
        html[data-theme="dark"] input{background:#1c1917!important;color:#f5f5f4!important}
        html[data-theme="dark"] .bg-stone-50{background:#1c1917!important}
        html[data-theme="dark"] [class*="text-black/"]{color:rgba(245,245,244,.55)!important}
        html[data-theme="dark"] .text-black{color:#f5f5f4!important}
        html[data-theme="dark"] .border-black{border-color:#f5f5f4!important}
    </style>
</head>
<body class="min-h-screen bg-[#fff3c9] text-black">
    <main class="grid min-h-screen lg:grid-cols-2">
        <section class="relative hidden overflow-hidden bg-black lg:block">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(132,204,22,0.35),_transparent_45%)]"></div>
            <div class="relative flex h-full flex-col justify-between p-12 text-white">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.3em] text-lime-300">STAFF ONLY</p>

                    @if(!empty($settings['logo_url']))
                        <img src="{{ $settings['logo_url'] }}" alt="{{ $settings['brand_name'] }}" class="mt-6 max-h-20 max-w-64 object-contain">
                    @else
                        <h1 class="mt-4 text-7xl font-black leading-none">{{ $settings['brand_name'] }}</h1>
                    @endif

                    <p class="mt-5 max-w-md text-lg font-bold text-white/60">
                        {{ $settings['brand_tagline'] }}. Inventory, products, categories, orders, and cashier operations in one console.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                        <p class="text-xs font-black uppercase tracking-widest text-white/40">CATALOG</p>
                        <p class="mt-2 text-2xl font-black">Products</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                        <p class="text-xs font-black uppercase tracking-widest text-white/40">OPERATIONS</p>
                        <p class="mt-2 text-2xl font-black">POS</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="flex items-center justify-center p-6 sm:p-10">
            <form method="POST"
                  action="/admin/login"
                  class="w-full max-w-md rounded-[2rem] border-2 border-black bg-white p-7 shadow-[10px_10px_0_#111] sm:p-9">
                @csrf

                <div class="lg:hidden">
                    @if(!empty($settings['logo_url']))
                        <img src="{{ $settings['logo_url'] }}" alt="{{ $settings['brand_name'] }}" class="max-h-14 max-w-44 object-contain">
                    @else
                        <p class="text-4xl font-black">{{ $settings['brand_name'] }}</p>
                    @endif
                </div>

                <div class="mt-2">
                    <p class="text-xs font-black uppercase tracking-[0.25em] text-black/40">ADMIN / CASHIER</p>
                    <h2 class="mt-1 text-3xl font-black">Sign in</h2>
                    <p class="mt-2 text-sm font-bold text-black/50">Customer kiosk users do not need an account.</p>
                </div>

                @if($errors->any())
                    <div class="mt-6 rounded-2xl border-2 border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <label class="mt-7 block font-black">
                    EMAIL
                    <input name="email"
                           type="email"
                           autocomplete="username"
                           value="{{ old('email') }}"
                           class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-4"
                           required>
                </label>

                <label class="mt-5 block font-black">
                    PASSWORD
                    <div class="mt-2 flex overflow-hidden rounded-2xl border-2 border-black">
                        <input id="password"
                               name="password"
                               type="password"
                               autocomplete="current-password"
                               class="min-w-0 flex-1 border-0 px-4 py-4 outline-none"
                               required>
                        <button type="button"
                                id="toggle-password"
                                class="border-l-2 border-black bg-stone-50 px-4 font-black">
                            SHOW
                        </button>
                    </div>
                </label>

                <button class="mt-7 w-full rounded-2xl bg-black py-4 text-lg font-black text-white">
                    SIGN IN
                </button>

                <a href="{{ url('/') }}"
                   class="mt-4 block text-center text-sm font-black text-black/40 hover:text-black">
                    ← Return to kiosk
                </a>
            </form>
        </section>
    </main>

    <script>
        const password = document.getElementById('password');
        const toggle = document.getElementById('toggle-password');

        toggle.addEventListener('click', () => {
            const visible = password.type === 'text';
            password.type = visible ? 'password' : 'text';
            toggle.textContent = visible ? 'SHOW' : 'HIDE';
        });
    </script>
</body>
</html>