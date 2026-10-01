@extends('layouts.kiosk')

@section('content')
@php
    $variants = $product['variants'] ?? [];
    $colors = collect($variants)->pluck('color')->filter()->unique()->values();
    $defaultColor = $colors->first();
    $allOut = count($variants) === 0 || collect($variants)->every(fn($v) => (int)($v['stock'] ?? 0) <= 0);
@endphp

<main class="mx-auto max-w-6xl px-5 py-8">
    <a href="{{ url('/menu') }}" class="font-black uppercase text-black/50">← Back to footwear</a>

    <div class="mt-5 grid gap-8 md:grid-cols-2">
        <div class="overflow-hidden rounded-3xl border-2 border-black bg-white">
            <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}" class="aspect-square h-full w-full object-cover">
        </div>

        <div>
            <p class="font-bold uppercase tracking-widest text-black/50">{{ $product['category_name'] }}</p>
            <h1 class="mt-2 text-4xl font-black">{{ $product['name'] }}</h1>
            <p class="mt-3 text-2xl font-black">₱{{ number_format($product['price'], 2) }}</p>
            <p class="mt-5 text-black/60">{{ $product['description'] }}</p>

            @if($allOut)
                <div class="mt-8 rounded-2xl border-2 border-red-500 bg-red-50 p-5 text-center">
                    <p class="text-2xl font-black text-red-600">OUT OF STOCK</p>
                    <p class="mt-1 font-bold text-red-700/70">This footwear is currently unavailable.</p>
                </div>
                <a href="{{ url('/menu') }}" class="mt-5 block rounded-2xl bg-black py-5 text-center text-lg font-black text-white">
                    BROWSE OTHER FOOTWEAR
                </a>
            @else
                <form id="add-to-cart-form" action="{{ url('/cart/add') }}" method="POST" class="mt-8 space-y-6">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product['id'] }}">

                    <div>
                        <label class="font-black">COLOR</label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($colors as $color)
                                @php
                                    $colorStock = collect($variants)->filter(fn($v) => ($v['color'] ?? '') === $color)->sum(fn($v) => (int)($v['stock'] ?? 0));
                                @endphp
                                <label>
                                    <input type="radio"
                                           name="color"
                                           value="{{ $color }}"
                                           class="peer sr-only color-option"
                                           data-color="{{ $color }}"
                                           {{ $color === $defaultColor ? 'checked' : '' }}
                                           {{ $colorStock <= 0 ? 'disabled' : '' }}>
                                    <span class="block rounded-xl border-2 border-black px-5 py-3 font-black {{ $colorStock <= 0 ? 'cursor-not-allowed bg-gray-200 text-gray-400 line-through' : 'cursor-pointer bg-white peer-checked:bg-lime-400' }}">
                                        {{ $color }}
                                        <span class="ml-1 text-xs opacity-60">({{ $colorStock }} total)</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label class="font-black">SIZE</label>
                            <span id="stock-summary" class="text-sm font-black text-lime-700"></span>
                        </div>

                        <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                            @foreach($variants as $i => $variant)
                                @php($stock = (int)($variant['stock'] ?? 0))
                                <label class="size-option-wrap" data-size="{{ $variant['size'] }}" data-color="{{ $variant['color'] }}" data-stock="{{ $stock }}">
                                    <input type="radio"
                                           name="size"
                                           value="{{ $variant['size'] }}"
                                           class="peer sr-only size-option"
                                           data-size="{{ $variant['size'] }}"
                                           data-color="{{ $variant['color'] }}"
                                           data-stock="{{ $stock }}"
                                           {{ $i === 0 && $stock > 0 ? 'checked' : '' }}
                                           {{ $stock <= 0 ? 'disabled' : '' }}>
                                    <span class="block rounded-xl border-2 border-black px-3 py-3 text-center font-black {{ $stock <= 0 ? 'cursor-not-allowed bg-gray-200 text-gray-400' : 'cursor-pointer bg-white peer-checked:bg-lime-400' }}">
                                        {{ $variant['size'] }}
                                        <span class="block text-xs font-bold opacity-60 stock-label">{{ $stock > 0 ? $stock . ' left' : 'OUT' }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="font-black">QUANTITY</label>
                        <div class="mt-2 flex items-center gap-3">
                            <button type="button" id="minus" class="h-12 w-12 rounded-xl border-2 border-black bg-white text-2xl font-black">−</button>
                            <input id="quantity" type="number" name="quantity" value="1" min="1" max="1"
                                   class="h-12 w-24 rounded-xl border-2 border-black bg-white text-center font-black">
                            <button type="button" id="plus" class="h-12 w-12 rounded-xl border-2 border-black bg-white text-2xl font-black">+</button>
                        </div>
                    </div>

                    <button id="add-button" class="w-full rounded-2xl bg-black py-5 text-lg font-black text-white disabled:cursor-not-allowed disabled:bg-gray-400" disabled>
                        ADD TO CART
                    </button>
                </form>
            @endif
        </div>
    </div>
</main>

@if(!$allOut)
<script>
    const variants = @json($variants);
    const colorInputs = document.querySelectorAll('.color-option');
    const sizeInputs = document.querySelectorAll('.size-option');
    const quantity = document.getElementById('quantity');
    const addButton = document.getElementById('add-button');
    const stockSummary = document.getElementById('stock-summary');

    function currentColor() {
        return document.querySelector('.color-option:checked')?.value || '';
    }

    function refreshVariants() {
        const color = currentColor();
        let firstAvailable = null;
        let totalForColor = 0;

        sizeInputs.forEach(input => {
            const variant = variants.find(v => String(v.size) === String(input.dataset.size) && v.color === color);
            const stock = variant ? Number(variant.stock || 0) : 0;
            const wrap = input.closest('.size-option-wrap');
            const label = wrap?.querySelector('.stock-label');

            input.dataset.stock = stock;
            input.disabled = stock <= 0;
            if (label) {
                label.textContent = stock > 0 ? stock + ' left' : 'OUT';
                label.classList.toggle('text-red-500', stock <= 0);
            }
            wrap?.classList.toggle('opacity-60', stock <= 0);

            if (stock > 0) {
                totalForColor += stock;
                if (!firstAvailable) firstAvailable = input;
            }
        });

        const current = document.querySelector('.size-option:checked');
        if (!current || current.disabled || Number(current.dataset.stock) <= 0) {
            document.querySelectorAll('.size-option').forEach(input => input.checked = false);
            if (firstAvailable) firstAvailable.checked = true;
        }

        const selected = document.querySelector('.size-option:checked');
        const selectedStock = selected ? Number(selected.dataset.stock) : 0;
        quantity.max = Math.max(1, selectedStock);
        if (Number(quantity.value) > selectedStock) quantity.value = Math.max(1, selectedStock);
        if (Number(quantity.value) < 1) quantity.value = 1;

        addButton.disabled = !selected || selectedStock <= 0;
        stockSummary.textContent = totalForColor > 0 ? totalForColor + ' total in this color' : 'Out of stock';
    }

    colorInputs.forEach(input => input.addEventListener('change', refreshVariants));
    sizeInputs.forEach(input => input.addEventListener('change', refreshVariants));

    document.getElementById('minus').addEventListener('click', () => {
        quantity.value = Math.max(1, Number(quantity.value || 1) - 1);
    });

    document.getElementById('plus').addEventListener('click', () => {
        quantity.value = Math.min(Number(quantity.max || 1), Number(quantity.value || 1) + 1);
    });

    refreshVariants();
</script>
@endif
@endsection
