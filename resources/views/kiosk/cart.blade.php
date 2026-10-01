@extends('layouts.kiosk')

@section('content')
@php($settings = app(\App\Services\SettingsService::class)->all())
<main class="mx-auto max-w-5xl px-5 pt-8 pb-52">
    <div class="relative grid grid-cols-[1fr_auto_1fr] items-center gap-3">
        <a href="{{ url('/menu') }}"
           class="justify-self-start rounded-2xl border-2 border-black bg-white px-5 py-3 font-black">
            ← SHOP MORE
        </a>

        <div class="text-center">
            <p class="text-xs font-black uppercase tracking-widest text-black/40">{{ $settings['brand_name'] }}</p>
            <h1 class="text-4xl font-black">YOUR ORDER</h1>
        </div>

        <div></div>
    </div>

    @if(session('error'))
        <div class="mt-5 rounded-2xl border-2 border-red-500 bg-red-50 p-4 font-bold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="mt-6 space-y-4">
        @if(count($cart) > 0)
            @foreach($cart as $key => $item)
            <div class="flex flex-col gap-4 rounded-3xl border-2 border-black bg-white p-4 sm:flex-row sm:items-center">
                <img src="{{ $item['image_url'] }}"
                     alt="{{ $item['name'] }}"
                     class="h-28 w-28 rounded-2xl object-cover">

                <div class="min-w-0 flex-1">
                    <h2 class="font-black">{{ $item['name'] }}</h2>
                    <p class="text-sm font-bold text-black/50">
                        Size {{ $item['size'] }} · {{ $item['color'] }}
                    </p>

                    @if(!empty($item['sale_price']))
                        <div class="mt-1 flex items-center gap-2">
                            <p class="font-black text-red-600">₱{{ number_format($item['price'], 2) }}</p>
                            <p class="text-sm font-bold text-black/40 line-through">
                                ₱{{ number_format($item['regular_price'], 2) }}
                            </p>
                        </div>
                        <span class="mt-1 inline-block rounded-full bg-red-100 px-2 py-1 text-[10px] font-black text-red-600">
                            SALE
                        </span>
                    @else
                        <p class="mt-1 font-black">₱{{ number_format($item['price'], 2) }}</p>
                    @endif
                </div>

                <form method="POST"
                      action="{{ url('/cart/update') }}"
                      class="flex items-center gap-2"
                      data-cart-update>
                    @csrf
                    <input type="hidden" name="key" value="{{ $key }}">
                    <input type="hidden"
                           name="quantity"
                           value="{{ $item['quantity'] }}"
                           min="1"
                           max="{{ $item['stock'] }}"
                           data-qty-input>

                    <button type="button"
                            data-qty-minus-ten
                            aria-label="Decrease quantity by 10"
                            class="hidden h-11 rounded-xl border-2 border-black bg-white px-3 font-black">
                        −10
                    </button>

                    <button type="button"
                            data-qty-minus-five
                            aria-label="Decrease quantity by 5"
                            class="hidden h-11 rounded-xl border-2 border-black bg-white px-3 font-black">
                        −5
                    </button>

                    <button type="button"
                            data-qty-minus
                            aria-label="Decrease quantity"
                            class="h-11 w-11 rounded-xl border-2 border-black bg-white text-xl font-black">
                        −
                    </button>

                    <span data-qty-label
                          class="grid h-11 min-w-14 place-items-center rounded-xl border-2 border-black bg-[#fff3c9] px-3 font-black">
                        {{ $item['quantity'] }}
                    </span>

                    <button type="button"
                            data-qty-plus
                            aria-label="Increase quantity"
                            class="h-11 w-11 rounded-xl border-2 border-black bg-white text-xl font-black">
                        +
                    </button>

                    <button type="button"
                            data-qty-plus-five
                            aria-label="Increase quantity by 5"
                            class="h-11 rounded-xl border-2 border-black bg-white px-3 font-black">
                        +5
                    </button>

                    <button type="button"
                            data-qty-plus-ten
                            aria-label="Increase quantity by 10"
                            class="h-11 rounded-xl border-2 border-black bg-white px-3 font-black">
                        +10
                    </button>
                </form>

                <form method="POST" action="{{ url('/cart/remove') }}">
                    @csrf
                    <input type="hidden" name="key" value="{{ $key }}">
                    <button class="font-black text-red-600">REMOVE</button>
                </form>
            </div>
            @endforeach
        @else
            <div class="rounded-3xl border-2 border-black bg-white p-12 text-center font-black">
                <p class="text-2xl">Your cart is empty.</p>
                <a href="{{ url('/menu') }}"
                   class="mt-5 inline-block rounded-2xl bg-black px-6 py-4 font-black text-white">
                    BROWSE FOOTWEAR
                </a>
            </div>
        @endif
    </div>
</main>

@if($cart)
    <div class="fixed inset-x-0 bottom-0 z-40 border-t-4 border-black bg-[#d7e84e] shadow-[0_-10px_30px_rgba(0,0,0,0.15)]">
        <div class="mx-auto flex min-h-[112px] max-w-5xl flex-col gap-5 px-5 py-5 sm:min-h-[124px] sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-black/50">ORDER TOTAL</p>
                <p class="text-5xl font-black leading-none sm:text-6xl">₱{{ number_format($total, 2) }}</p>
            </div>

            <button type="button"
                    id="open-order-modal"
                    class="w-full rounded-2xl bg-black px-10 py-6 text-center text-xl font-black text-white sm:w-auto">
                GENERATE ORDER
            </button>
        </div>
    </div>
@endif

@if($cart)
    <div id="order-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-5">
        <div class="w-full max-w-2xl rounded-[2rem] border-4 border-black bg-[#fff3c9] p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.25em] text-black/50">FINAL CHECK</p>
                    <h2 class="mt-1 text-3xl font-black">Generate this order?</h2>
                </div>

                <button type="button"
                        id="close-order-modal"
                        class="h-11 w-11 rounded-full border-2 border-black bg-white text-xl font-black">
                    ×
                </button>
            </div>

            <div class="step-scroll mt-5 max-h-[45vh] space-y-3 overflow-y-auto rounded-2xl border-2 border-black bg-white p-4">
                @foreach($cart as $item)
                    <div class="flex items-center justify-between gap-4 border-b border-black/10 pb-3 last:border-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="font-black">{{ $item['name'] }}</p>
                            <p class="text-sm font-bold text-black/50">
                                {{ $item['size'] }} · {{ $item['color'] }} × {{ $item['quantity'] }}
                            </p>
                        </div>
                        <p class="shrink-0 font-black">
                            ₱{{ number_format($item['price'] * $item['quantity'], 2) }}
                        </p>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-widest text-black/40">ORDER TOTAL</p>
                    <p class="text-4xl font-black">₱{{ number_format($total, 2) }}</p>
                </div>

                <form method="POST" action="{{ url('/checkout') }}">
                    @csrf
                    <button class="w-full rounded-2xl bg-black px-7 py-4 font-black text-white sm:w-auto">
                        GENERATE & GO TO CASHIER
                    </button>
                </form>
            </div>

            <p class="mt-4 text-center text-sm font-bold text-black/50">
                No payment is taken at the kiosk. Present the generated order number to the cashier.
            </p>
        </div>
    </div>

    <script>
        const modal = document.getElementById('order-modal');
        const openButton = document.getElementById('open-order-modal');
        const closeButton = document.getElementById('close-order-modal');

        openButton?.addEventListener('click', () => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });

        closeButton?.addEventListener('click', () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });

        modal?.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        });

        document.querySelectorAll('[data-cart-update]').forEach(form => {
            const input = form.querySelector('[data-qty-input]');
            const label = form.querySelector('[data-qty-label]');
            const minusTen = form.querySelector('[data-qty-minus-ten]');
            const minusFive = form.querySelector('[data-qty-minus-five]');
            const minus = form.querySelector('[data-qty-minus]');
            const plus = form.querySelector('[data-qty-plus]');
            const plusFive = form.querySelector('[data-qty-plus-five]');
            const plusTen = form.querySelector('[data-qty-plus-ten]');
            const max = Math.min(Number(input.max || 20), Number(window.STEPORDER_MAX_QTY || {{ (int)$settings['max_cart_quantity'] }}));

            const submit = () => form.submit();

            const refreshButtons = (value) => {
                minusFive.classList.toggle('hidden', value <= 5);
                minusTen.classList.toggle('hidden', value <= 10);
                plusFive.classList.toggle('hidden', value + 5 > max);
                plusTen.classList.toggle('hidden', value + 10 > max);
            };

            const setQuantity = (value) => {
                value = Math.max(1, Math.min(max, value));
                input.value = value;
                label.textContent = value;
                refreshButtons(value);
            };

            minusTen.addEventListener('click', () => {
                setQuantity(Number(input.value || 1) - 10);
                submit();
            });

            minusFive.addEventListener('click', () => {
                setQuantity(Number(input.value || 1) - 5);
                submit();
            });

            minus.addEventListener('click', () => {
                setQuantity(Number(input.value || 1) - 1);
                submit();
            });

            plus.addEventListener('click', () => {
                setQuantity(Number(input.value || 1) + 1);
                submit();
            });

            plusFive.addEventListener('click', () => {
                setQuantity(Number(input.value || 1) + 5);
                submit();
            });

            plusTen.addEventListener('click', () => {
                setQuantity(Number(input.value || 1) + 10);
                submit();
            });

            refreshButtons(Number(input.value || 1));
        });
    </script>
@endif

@endsection
