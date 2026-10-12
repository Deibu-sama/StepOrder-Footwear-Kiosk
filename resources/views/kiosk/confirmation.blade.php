@extends('layouts.kiosk')

@section('content')
@php($settings = app(\App\Services\SettingsService::class)->all())
<main class="mx-auto max-w-3xl px-5 py-12 text-center">
    <div class="mx-auto max-w-xl rounded-[2rem] border-2 border-black bg-lime-300 p-8">
        <p class="font-black uppercase tracking-widest">ORDER GENERATED</p>
        <h1 class="mt-3 text-8xl font-black">{{ $order['order_number'] }}</h1>

        <p class="mt-4 text-xl font-bold">
            Please proceed to the cashier and present this order number.
        </p>

        <div class="mt-8 rounded-2xl bg-white p-5 text-left">
            @if(isset($order['net_sales']))
                <div class="flex justify-between gap-4 text-sm font-bold text-black/60">
                    <span>Sales before VAT</span>
                    <span>{{ $settings['currency_symbol'] }}{{ number_format($order['net_sales'], 2) }}</span>
                </div>
            @endif
            @if(!empty($order['vat_enabled']))
                <div class="mt-2 flex justify-between gap-4 text-sm font-bold text-black/60">
                    <span>Output VAT ({{ number_format($order['vat_rate'] ?? 0, 2) }}%{{ !empty($order['vat_inclusive']) ? ', included' : '' }})</span>
                    <span>{{ $settings['currency_symbol'] }}{{ number_format($order['vat_amount'] ?? 0, 2) }}</span>
                </div>
            @endif
            <div class="mt-3 flex justify-between gap-4 border-t border-black/10 pt-3 font-black">
                <span>TOTAL DUE</span>
                <span>{{ $settings['currency_symbol'] }}{{ number_format($order['total'], 2) }}</span>
            </div>
            <div class="mt-2 text-sm font-bold text-black/50">
                Payment is completed at the cashier.
            </div>
        </div>

        <div class="mt-6 rounded-2xl border-2 border-black bg-white p-5">
            <p class="text-sm font-black uppercase tracking-widest text-black/40">RETURNING TO START SCREEN</p>
            <div class="mt-2 text-5xl font-black">
                <span id="countdown">{{ (int)$settings['confirmation_seconds'] }}</span>
                <span class="text-2xl">seconds</span>
            </div>
            <div class="mt-4 h-3 overflow-hidden rounded-full bg-black/10">
                <div id="countdown-bar" class="h-full w-full origin-left bg-black transition-transform duration-1000 ease-linear"></div>
            </div>
        </div>

        @if(!empty($order['receipt_token']))
            <a href="{{ url('/order/'.$order['order_number'].'/receipt/'.$order['receipt_token']) }}" target="_blank" rel="noopener" class="mt-6 block rounded-2xl border-2 border-black bg-white py-4 font-black text-black">PRINT ORDER SLIP / RECEIPT</a>
        @endif
        <a href="{{ url('/') }}" class="mt-6 block rounded-2xl bg-black py-4 font-black text-white">
            RETURN NOW
        </a>
    </div>
</main>

<script>
    let remaining = {{ (int)$settings['confirmation_seconds'] }};
    const duration = remaining;
    const countdown = document.getElementById('countdown');
    const bar = document.getElementById('countdown-bar');

    const timer = setInterval(() => {
        remaining -= 1;
        countdown.textContent = remaining;
        bar.style.transform = 'scaleX(' + (remaining / duration) + ')';

        if (remaining <= 0) {
            clearInterval(timer);
            window.location.href = '{{ url('/') }}';
        }
    }, 1000);
</script>
@endsection
