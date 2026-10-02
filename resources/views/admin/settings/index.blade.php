@extends('layouts.admin')

@section('content')
<div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
    <div>
        <p class="text-xs font-black uppercase tracking-[0.25em] text-black/40">CONFIGURATION</p>
        <h1 class="mt-1 text-4xl font-black tracking-tight sm:text-5xl">Settings</h1>
        <p class="mt-2 max-w-3xl font-bold text-black/50">
            Control branding, theme, kiosk behavior, ordering rules, catalog display, and maintenance mode from one place.
        </p>
    </div>

    <div class="flex flex-wrap gap-2">
        <a href="{{ url('/') }}" target="_blank" class="rounded-2xl border-2 border-black bg-white px-4 py-3 font-black">PREVIEW KIOSK</a>
        <form method="POST" action="/admin/settings/reset"
              onsubmit="return confirm('Restore all StepOrder settings to their defaults?')">
            @csrf
            <button class="rounded-2xl border-2 border-red-200 bg-red-50 px-4 py-3 font-black text-red-600">RESET DEFAULTS</button>
        </form>
    </div>
</div>

<form method="POST" action="/admin/settings" class="mt-7 space-y-6">
    @csrf
    @method('PUT')

    <section class="rounded-3xl border border-black/10 bg-white p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-black/40">01 · BRANDING</p>
                <h2 class="mt-1 text-2xl font-black">Brand identity</h2>
                <p class="mt-1 text-sm font-bold text-black/40">These values appear across the kiosk and management console.</p>
            </div>
            <div class="hidden h-11 w-11 place-items-center rounded-2xl bg-lime-300 text-xl font-black sm:grid">A</div>
        </div>

        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <label class="block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Brand name</span>
                <input name="brand_name" value="{{ old('brand_name', $settings['brand_name']) }}"
                       class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
            </label>

            <label class="block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Short brand name</span>
                <input name="brand_short_name" value="{{ old('brand_short_name', $settings['brand_short_name']) }}"
                       class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
            </label>

            <label class="block md:col-span-2">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Brand tagline</span>
                <input name="brand_tagline" value="{{ old('brand_tagline', $settings['brand_tagline']) }}"
                       class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
            </label>

            <label class="block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Admin console label</span>
                <input name="admin_label" value="{{ old('admin_label', $settings['admin_label']) }}"
                       class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
            </label>

            <label class="block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Logo URL</span>
                <input name="logo_url" type="url" value="{{ old('logo_url', $settings['logo_url']) }}"
                       placeholder="https://example.com/logo.png"
                       class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
                <span class="mt-1 block text-xs font-bold text-black/40">URL only. No file upload is used.</span>
                <span class="mt-2 block text-xs font-bold text-lime-700/80">
                    Recommended: transparent PNG or SVG, preferably around 1600 × 500 px (about 3.2:1). Keep the logo artwork centered with a small transparent margin around it for clean scaling.
                </span>
            </label>

            <label class="block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Favicon URL</span>
                <input name="favicon_url" type="url" value="{{ old('favicon_url', $settings['favicon_url']) }}"
                       placeholder="https://example.com/favicon.png"
                       class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
            </label>

            <div class="rounded-2xl border-2 border-dashed border-black/20 bg-stone-50 p-4">
                <p class="text-xs font-black uppercase tracking-widest text-black/40">Current logo</p>
                <div class="mt-3 flex min-h-20 items-center justify-center rounded-2xl bg-stone-100 p-4">

                    @if($settings['logo_url'])
                        <img src="{{ $settings['logo_url'] }}" alt="Brand logo" class="max-h-16 max-w-[220px] object-contain">
                    @else
                        <span class="text-2xl font-black"><span class="text-lime-600">STEP</span>ORDER</span>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-black/10 bg-white p-6">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-black/40">02 · APPEARANCE</p>
            <h2 class="mt-1 text-2xl font-black">Theme & visual system</h2>
            <p class="mt-1 text-sm font-bold text-black/40">Choose how the kiosk and admin console should look.</p>
        </div>

        <div class="mt-6 grid gap-5 lg:grid-cols-4">
            <label class="block lg:col-span-1">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Theme mode</span>
                <select name="theme_mode" class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
                    <option value="light" @selected($settings['theme_mode'] === 'light')>☀ Light</option>
                    <option value="dark" @selected($settings['theme_mode'] === 'dark')>☾ Dark</option>
                    <option value="system" @selected($settings['theme_mode'] === 'system')>◐ System preference</option>
                </select>
                <span class="mt-1 block text-xs font-bold text-black/40">System follows the device's OS preference.</span>
            </label>

            <label class="block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Primary color</span>
                <div class="mt-2 flex gap-2">
                    <input id="primary_color_picker" type="color" value="{{ old('primary_color', $settings['primary_color']) }}" class="h-12 w-16 rounded-xl border-2 border-black bg-white p-1">
                    <input id="primary_color" name="primary_color" value="{{ old('primary_color', $settings['primary_color']) }}" class="min-w-0 flex-1 rounded-2xl border-2 border-black px-4 py-3 font-mono font-bold">
                </div>
            </label>

            <label class="block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Strong accent</span>
                <div class="mt-2 flex gap-2">
                    <input id="primary_strong_color_picker" type="color" value="{{ old('primary_strong_color', $settings['primary_strong_color']) }}" class="h-12 w-16 rounded-xl border-2 border-black bg-white p-1">
                    <input id="primary_strong_color" name="primary_strong_color" value="{{ old('primary_strong_color', $settings['primary_strong_color']) }}" class="min-w-0 flex-1 rounded-2xl border-2 border-black px-4 py-3 font-mono font-bold">
                </div>
            </label>

            <label class="block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Accent text color</span>
                <div class="mt-2 flex gap-2">
                    <input id="primary_text_color_picker" type="color" value="{{ old('primary_text_color', $settings['primary_text_color']) }}" class="h-12 w-16 rounded-xl border-2 border-black bg-white p-1">
                    <input id="primary_text_color" name="primary_text_color" value="{{ old('primary_text_color', $settings['primary_text_color']) }}" class="min-w-0 flex-1 rounded-2xl border-2 border-black px-4 py-3 font-mono font-bold">
                </div>
            </label>
        </div>

        <div class="mt-5 flex items-center justify-between gap-4 rounded-2xl border border-black/10 bg-stone-50 p-4">
            <div>
                <p class="font-black">Reduced motion</p>
                <p class="text-sm font-bold text-black/40">Disable animated transitions and kiosk slide fades.</p>
            </div>
            <input type="hidden" name="reduced_motion" value="0">
            <input type="checkbox" name="reduced_motion" value="1" class="h-6 w-6 accent-lime-600" @checked($settings['reduced_motion'])>
        </div>
    </section>

    <section class="rounded-3xl border border-black/10 bg-white p-6">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-black/40">03 · KIOSK EXPERIENCE</p>
            <h2 class="mt-1 text-2xl font-black">Start screen & behavior</h2>
            <p class="mt-1 text-sm font-bold text-black/40">Change what customers see before they begin and how the kiosk behaves.</p>
        </div>

        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <label class="block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Kiosk label</span>
                <input name="kiosk_label" value="{{ old('kiosk_label', $settings['kiosk_label']) }}"
                       class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3 font-bold">
            </label>

            <label class="block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Start button text</span>
                <input name="start_button_text" value="{{ old('start_button_text', $settings['start_button_text']) }}"
                       class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3 font-bold">
            </label>

            <label class="block md:col-span-2">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Start screen title</span>
                <input name="start_title" value="{{ old('start_title', $settings['start_title']) }}"
                       class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3 font-black">
            </label>

            <label class="block md:col-span-2">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Start screen description</span>
                <textarea name="start_description" rows="3" class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3 font-bold">{{ old('start_description', $settings['start_description']) }}</textarea>
            </label>

            <label class="block md:col-span-2">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Start screen footer</span>
                <input name="start_footer_text" value="{{ old('start_footer_text', $settings['start_footer_text']) }}"
                       class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3 font-bold">
            </label>
        </div>

        <div class="mt-6 rounded-2xl border-2 border-black bg-stone-50 p-5">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="font-black">Hero slideshow</p>
                    <p class="text-sm font-bold text-black/40">Use up to five public image URLs on the start screen.</p>
                </div>
                <div>
                    <input type="hidden" name="hero_enabled" value="0">
                    <label class="flex items-center gap-2 font-black">
                        <input type="checkbox" name="hero_enabled" value="1" class="h-5 w-5 accent-lime-600" @checked($settings['hero_enabled'])>
                        Enabled
                    </label>
                </div>
            </div>

            <label class="mt-5 block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Slide interval (milliseconds)</span>
                <input type="number" name="hero_interval" value="{{ old('hero_interval', $settings['hero_interval']) }}" min="1500" max="15000"
                       class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
            </label>

            <div class="mt-5 grid gap-3 md:grid-cols-2">
                @for($i = 0; $i < 5; $i++)
                    <label class="block">
                        <span class="text-xs font-black uppercase tracking-widest text-black/40">Slide {{ $i + 1 }} URL</span>
                        <input type="url" name="hero_slides[]" value="{{ old('hero_slides.'.$i, $settings['hero_slides'][$i] ?? '') }}"
                               placeholder="https://..."
                               class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
                    </label>
                @endfor
            </div>
        </div>

        <div class="mt-5 grid gap-5 md:grid-cols-3">
            <label class="block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Confirmation duration</span>
                <input type="number" name="confirmation_seconds" value="{{ old('confirmation_seconds', $settings['confirmation_seconds']) }}" min="5" max="60"
                       class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3 font-bold">
                <span class="mt-1 block text-xs font-bold text-black/40">Seconds before the kiosk returns to start.</span>
            </label>

            <div class="rounded-2xl border border-black/10 bg-stone-50 p-4 md:col-span-2">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="font-black">Idle timeout</p>
                        <p class="text-sm font-bold text-black/40">Automatically return an inactive kiosk to the start screen.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="idle_enabled" value="0">
                        <input type="checkbox" name="idle_enabled" value="1" class="h-5 w-5 accent-lime-600" @checked($settings['idle_enabled'])>
                    </div>
                </div>
                <label class="mt-4 block">
                    <span class="text-xs font-black uppercase tracking-widest text-black/40">Idle seconds</span>
                    <input type="number" name="idle_seconds" value="{{ old('idle_seconds', $settings['idle_seconds']) }}" min="30" max="900"
                           class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
                </label>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-black/10 bg-white p-6">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-black/40">04 · CATALOG & ORDERING</p>
            <h2 class="mt-1 text-2xl font-black">Store rules</h2>
            <p class="mt-1 text-sm font-bold text-black/40">Configure order numbering, inventory warnings, and customer-facing filters.</p>
        </div>

        <div class="mt-6 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
            <label class="block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Currency symbol</span>
                <input name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol']) }}"
                       class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3 font-bold">
            </label>

            <label class="block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Currency code</span>
                <input name="currency_code" value="{{ old('currency_code', $settings['currency_code']) }}"
                       class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3 font-bold">
            </label>

            <label class="block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Order prefix</span>
                <input name="order_prefix" value="{{ old('order_prefix', $settings['order_prefix']) }}"
                       placeholder="e.g. SO-"
                       class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3 font-bold">
            </label>

            <label class="block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Order number digits</span>
                <input type="number" name="order_digits" value="{{ old('order_digits', $settings['order_digits']) }}" min="3" max="8"
                       class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3 font-bold">
            </label>
        </div>

        <div class="mt-5 grid gap-5 md:grid-cols-3">
            <label class="block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Max quantity / line</span>
                <input type="number" name="max_cart_quantity" value="{{ old('max_cart_quantity', $settings['max_cart_quantity']) }}" min="1" max="99"
                       class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3 font-bold">
            </label>

            <label class="block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Low-stock threshold</span>
                <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $settings['low_stock_threshold']) }}" min="0" max="99"
                       class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3 font-bold">
            </label>

            <label class="block">
                <span class="text-xs font-black uppercase tracking-widest text-black/40">Timezone</span>
                <input name="timezone" value="{{ old('timezone', $settings['timezone']) }}"
                       class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3 font-bold">
                <span class="mt-1 block text-xs font-bold text-black/40">Example: Asia/Manila</span>
            </label>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-2">
            @foreach([
                'show_out_of_stock' => ['Show out-of-stock products', 'Keep sold-out products visible but marked unavailable.'],
                'show_top_picks' => ['Show Top Pick filter', 'Show the ⭐ TOP PICKS shortcut to customers.'],
                'show_sale_filter' => ['Show Sale filter', 'Show the 🏷️ ON SALE shortcut to customers.'],
                'show_gender_filter' => ['Show Gender filter', 'Keep the gender selector visible in the catalog.'],
                'show_price_filter' => ['Show Price filter', 'Keep the price range selector visible in the catalog.'],
            ] as $key => $meta)
                <div class="flex items-center justify-between gap-4 rounded-2xl border border-black/10 bg-stone-50 p-4">
                    <div>
                        <p class="font-black">{{ $meta[0] }}</p>
                        <p class="text-sm font-bold text-black/40">{{ $meta[1] }}</p>
                    </div>
                    <div class="shrink-0">
                        <input type="hidden" name="{{ $key }}" value="0">
                        <input type="checkbox" name="{{ $key }}" value="1" class="h-6 w-6 accent-lime-600" @checked($settings[$key])>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-3xl border-2 border-red-200 bg-red-50 p-6">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-red-500">05 · OPERATIONS</p>
            <h2 class="mt-1 text-2xl font-black">Maintenance mode</h2>
            <p class="mt-1 text-sm font-bold text-red-900/60">
                Temporarily stop customer kiosk traffic while you update inventory or settings. The admin console remains available.
            </p>
        </div>

        <div class="mt-5 rounded-2xl border-2 border-amber-200 bg-white/70 p-5">
            <p class="text-xs font-black uppercase tracking-widest text-amber-700/60">PENDING ORDER LIFECYCLE</p>
            <h3 class="mt-1 text-xl font-black text-amber-950">Payment timeout & automatic cancellation</h3>
            <p class="mt-1 text-sm font-bold text-amber-900/60">
                Pending orders keep their selected stock reserved. Older unpaid orders are flagged for review, then automatically cancelled when they reach the expiry period.
            </p>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-widest text-black/40">Alert after (hours)</span>
                    <input type="number"
                           name="pending_order_warning_hours"
                           min="1"
                           max="168"
                           value="{{ old('pending_order_warning_hours', $settings['pending_order_warning_hours']) }}"
                           class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
                    <span class="mt-1 block text-xs font-bold text-black/40">
                        Example: 24 means unpaid orders become attention items after one day.
                    </span>
                </label>

                <label class="block">
                    <span class="text-xs font-black uppercase tracking-widest text-black/40">Auto-cancel after (days)</span>
                    <input type="number"
                           name="pending_order_expiry_days"
                           min="1"
                           max="30"
                           value="{{ old('pending_order_expiry_days', $settings['pending_order_expiry_days']) }}"
                           class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
                    <span class="mt-1 block text-xs font-bold text-black/40">
                        Reserved stock is restored and the cancellation is recorded in Activity & Records.
                    </span>
                </label>
            </div>
        </div>

        <div class="mt-5 rounded-2xl border-2 border-amber-200 bg-white/70 p-5">
            <p class="text-xs font-black uppercase tracking-widest text-amber-700/60">PENDING ORDER LIFECYCLE</p>
            <h3 class="mt-1 text-xl font-black text-amber-950">Payment timeout & automatic cancellation</h3>
            <p class="mt-1 text-sm font-bold text-amber-900/60">
                Pending orders keep their selected stock reserved. Older unpaid orders are flagged for review, then automatically cancelled when they reach the expiry period.
            </p>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-widest text-black/40">Alert after (hours)</span>
                    <input type="number"
                           name="pending_order_warning_hours"
                           min="1"
                           max="168"
                           value="{{ old('pending_order_warning_hours', $settings['pending_order_warning_hours']) }}"
                           class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
                    <span class="mt-1 block text-xs font-bold text-black/40">Example: 24 means unpaid orders become attention items after one day.</span>
                </label>

                <label class="block">
                    <span class="text-xs font-black uppercase tracking-widest text-black/40">Auto-cancel after (days)</span>
                    <input type="number"
                           name="pending_order_expiry_days"
                           min="1"
                           max="30"
                           value="{{ old('pending_order_expiry_days', $settings['pending_order_expiry_days']) }}"
                           class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
                    <span class="mt-1 block text-xs font-bold text-black/40">Reserved stock is restored and the cancellation is recorded in Activity & Records.</span>
                </label>
            </div>
        </div>

        <div class="mt-5 flex items-center justify-between gap-4 rounded-2xl border border-red-200 bg-white/70 p-4">
            <div>
                <p class="font-black">Disable customer kiosk</p>
                <p class="text-sm font-bold text-red-900/50">Customers will see the maintenance screen instead of the ordering interface.</p>
            </div>
            <div class="shrink-0">
                <input type="hidden" name="maintenance_mode" value="0">
                <input type="checkbox" name="maintenance_mode" value="1" class="h-6 w-6 accent-red-600" @checked($settings['maintenance_mode'])>
            </div>
        </div>

        <label class="mt-4 block">
            <span class="text-xs font-black uppercase tracking-widest text-red-700/60">Maintenance message</span>
            <textarea name="maintenance_message" rows="3" class="mt-2 w-full rounded-2xl border-2 border-red-200 bg-white px-4 py-3 font-bold">{{ old('maintenance_message', $settings['maintenance_message']) }}</textarea>
        </label>
    </section>

    <div class="sticky bottom-4 z-20">
        <div class="flex flex-col justify-between gap-3 rounded-3xl border-2 border-black bg-white p-4 shadow-[6px_6px_0_#111] sm:flex-row sm:items-center">
            <div>
                <p class="font-black">Ready to apply your configuration?</p>
                <p class="text-sm font-bold text-black/40">Changes are stored in Firestore and apply on the next request.</p>
            </div>
            <button class="rounded-2xl bg-black px-7 py-4 text-lg font-black text-white">SAVE SETTINGS</button>
        </div>
    </div>
</form>

@push('scripts')
<script>
    const bindColor = (pickerId, inputId) => {
        const picker = document.getElementById(pickerId);
        const input = document.getElementById(inputId);
        if (!picker || !input) return;
        picker.addEventListener('input', () => input.value = picker.value);
        input.addEventListener('input', () => {
            if (/^#[0-9A-Fa-f]{6}$/.test(input.value)) picker.value = input.value;
        });
    };

    bindColor('primary_color_picker', 'primary_color');
    bindColor('primary_strong_color_picker', 'primary_strong_color');
    bindColor('primary_text_color_picker', 'primary_text_color');
</script>
@endpush
@endsection
