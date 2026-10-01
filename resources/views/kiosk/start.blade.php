@php
    $settings = app(\App\Services\SettingsService::class)->all();
    $slides = array_values(array_filter($settings['hero_slides'] ?? []));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if(!empty($settings['favicon_url']))
        <link rel="icon" href="{{ $settings['favicon_url'] }}">
    @endif
    <title>{{ $settings['brand_name'] }} — Tap to Start</title>
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
        :root{--so-primary:{{ $settings['primary_color'] }};--so-primary-strong:{{ $settings['primary_strong_color'] }};--so-primary-text:{{ $settings['primary_text_color'] }}}
        html[data-theme="dark"] body{background:#171717!important;color:#f5f5f4}
        html[data-theme="dark"] .bg-white{background:#262626!important}
        .bg-lime-300{background:var(--so-primary)!important;color:var(--so-primary-text)!important}
        .text-lime-600{color:var(--so-primary-strong)!important}
        html.reduce-motion *,html.reduce-motion *::before,html.reduce-motion *::after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}
    </style>
</head>
<body class="min-h-screen bg-[#fff3c9] text-black">
    <main class="grid min-h-screen lg:grid-cols-[1.15fr_0.85fr]">
        @if($settings['hero_enabled'] && count($slides))
            <section class="relative min-h-[48vh] overflow-hidden bg-stone-200 lg:min-h-screen">
                <div id="hero-slides" class="absolute inset-0">
                    @foreach($slides as $index => $slide)
                        <img src="{{ $slide }}" class="slide absolute inset-0 h-full w-full object-cover opacity-{{ $index === 0 ? '100' : '0' }} transition-opacity duration-700" alt="Footwear">
                    @endforeach
                </div>

                <div class="absolute inset-0 bg-gradient-to-t from-black/65 via-black/10 to-transparent"></div>

                <div class="absolute bottom-8 left-8 right-8 text-white sm:bottom-12 sm:left-12">
                    <p class="text-sm font-black uppercase tracking-[0.3em] text-lime-300">{{ $settings['kiosk_label'] }}</p>
                    <p class="mt-2 whitespace-pre-line text-4xl font-black sm:text-6xl">{{ $settings['start_title'] }}</p>

                    <div id="slide-dots" class="mt-6 flex gap-2">
                        @foreach($slides as $index => $slide)
                            <span class="h-{{ $index === 0 ? '2.5' : '2.5' }} w-{{ $index === 0 ? '8' : '2.5' }} rounded-full bg-white{{ $index === 0 ? '' : '/40' }}"></span>
                        @endforeach
                    </div>
                </div>
            </section>
        @else
            <section class="hidden min-h-screen items-center justify-center bg-stone-900 p-12 text-center text-white lg:flex">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.3em] text-lime-300">{{ $settings['kiosk_label'] }}</p>
                    <p class="mt-4 text-6xl font-black">{{ $settings['start_title'] }}</p>
                </div>
            </section>
        @endif

        <section class="flex min-h-[52vh] items-center justify-center p-8 lg:min-h-screen">
            <div class="w-full max-w-xl text-center">
                <div class="flex items-center justify-center">
                    @if(!empty($settings['logo_url']))
                        <img src="{{ $settings['logo_url'] }}" alt="{{ $settings['brand_name'] }}" class="max-h-20 max-w-56 object-contain">
                    @else
                        <h1 class="text-6xl font-black leading-none sm:text-7xl">
                            <span class="text-lime-600">{{ $settings['brand_short_name'] }}</span>
                        </h1>
                    @endif
                </div>

                <p class="mt-5 text-lg font-bold uppercase tracking-[0.25em] text-black/50">{{ $settings['kiosk_label'] }}</p>

                <p class="mx-auto mt-5 max-w-md text-lg font-bold text-black/60">
                    {{ $settings['start_description'] }}
                </p>

                <a href="{{ url('/menu') }}"
                   class="mx-auto mt-10 flex min-h-28 w-full max-w-lg items-center justify-center rounded-[2rem] border-4 border-black bg-lime-300 px-8 text-3xl font-black shadow-[8px_8px_0_#111] transition hover:-translate-y-1 active:translate-y-1 active:shadow-[3px_3px_0_#111]">
                    {{ $settings['start_button_text'] }}
                </a>

                @if(!empty($settings['start_footer_text']))
                    <p class="mt-8 text-sm font-bold text-black/40">
                        {{ $settings['start_footer_text'] }}
                    </p>
                @endif

                <p class="mt-3 text-xs font-black uppercase tracking-widest text-black/25">
                    {{ $settings['brand_tagline'] }}
                </p>
            </div>
        </section>
    </main>

    @if($settings['hero_enabled'] && count($slides) > 1)
        <script>
            const slides = Array.from(document.querySelectorAll('.slide'));
            const dots = Array.from(document.querySelectorAll('#slide-dots span'));
            let current = 0;
            const interval = {{ (int)$settings['hero_interval'] }};

            setInterval(() => {
                slides[current].classList.replace('opacity-100','opacity-0');
                if (dots[current]) {
                    dots[current].classList.remove('w-8','bg-white');
                    dots[current].classList.add('w-2.5','bg-white/40');
                }

                current = (current + 1) % slides.length;

                slides[current].classList.replace('opacity-0','opacity-100');
                if (dots[current]) {
                    dots[current].classList.remove('w-2.5','bg-white/40');
                    dots[current].classList.add('w-8','bg-white');
                }
            }, interval);
        </script>
    @endif
</body>
</html>
