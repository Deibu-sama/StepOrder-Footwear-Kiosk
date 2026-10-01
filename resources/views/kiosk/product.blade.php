@extends('layouts.kiosk')

@section('content')
@php
    $variants = $product['variants'] ?? [];
    $colors = collect($variants)->pluck('color')->filter()->unique()->values();
    $sizes = collect($variants)->pluck('size')->filter()->unique()->sort()->values();
    $defaultColor = $colors->first();
    $colorImages = $product['color_images'] ?? [];
    $allOut = count($variants) === 0 || collect($variants)->every(fn($v) => (int)($v['stock'] ?? 0) <= 0);
@endphp

<main class="mx-auto max-w-6xl px-5 py-8">
    <a href="{{ url('/menu') }}" class="font-black uppercase text-black/50">← Back to footwear</a>

    <div class="mt-5 grid gap-8 md:grid-cols-2">
        <div class="overflow-hidden rounded-3xl border-2 border-black bg-white">
            <img id="product-image" src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}" class="aspect-square h-full w-full object-cover transition-opacity duration-200">
        </div>

        <div>
            <div class="flex flex-wrap items-center gap-2">
                <p class="font-bold uppercase tracking-widest text-black/50">{{ $product['category_name'] }}</p>
                <span class="rounded-full bg-white px-3 py-1 text-xs font-black uppercase">{{ $product['gender'] ?? 'Unisex' }}</span>
                @if(!empty($product['is_top_pick']) || !empty($product['is_most_bought']))
                    <span class="rounded-full bg-black px-3 py-1 text-xs font-black text-white">⭐ TOP PICK</span>
                @endif
            </div>
            <h1 class="mt-2 text-4xl font-black">{{ $product['name'] }}</h1>
            @php
                $regularPrice = (float)($product['price'] ?? 0);
                $salePrice = isset($product['sale_price']) && (float)$product['sale_price'] > 0 && (float)$product['sale_price'] < $regularPrice
                    ? (float)$product['sale_price']
                    : null;
                $discount = $salePrice ? round((($regularPrice - $salePrice) / $regularPrice) * 100) : 0;
            @endphp
            @if($salePrice)
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <span class="rounded-full bg-red-500 px-3 py-1 text-sm font-black text-white">{{ $discount }}% OFF</span>
                    <span class="text-3xl font-black text-red-600">₱{{ number_format($salePrice, 2) }}</span>
                    <span class="font-bold text-black/40 line-through">₱{{ number_format($regularPrice, 2) }}</span>
                </div>
            @else
                <p class="mt-3 text-2xl font-black">₱{{ number_format($regularPrice, 2) }}</p>
            @endif
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
                            @foreach($sizes as $size)
                                @php
                                    $initialVariant = collect($variants)->first(fn($v) => ($v['size'] ?? '') === $size && ($v['color'] ?? '') === $defaultColor);
                                    $stock = (int)($initialVariant['stock'] ?? 0);
                                @endphp
                                <label class="size-option-wrap" data-size="{{ $size }}">
                                    <input type="radio"
                                           name="size"
                                           value="{{ $size }}"
                                           class="peer sr-only size-option"
                                           data-size="{{ $size }}"
                                           data-stock="{{ $stock }}"
                                           {{ $stock > 0 && !$loop->first ? '' : ($stock > 0 ? 'checked' : '') }}
                                           {{ $stock <= 0 ? 'disabled' : '' }}>
                                    <span class="block rounded-xl border-2 border-black px-3 py-3 text-center font-black {{ $stock <= 0 ? 'cursor-not-allowed bg-gray-200 text-gray-400' : 'cursor-pointer bg-white peer-checked:bg-lime-400' }}">
                                        {{ $size }}
                                        <span class="block text-xs font-bold opacity-60 stock-label">{{ $stock > 0 ? $stock . ' left' : 'OUT' }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>                    </div>

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
    const productImage = document.getElementById('product-image');
    const colorImages = @json($colorImages);
    const defaultImage = @json($product['image_url'] ?? '');

    function currentColor() {
        return document.querySelector('.color-option:checked')?.value || '';
    }

    function refreshVariants() {
        const color = currentColor();

        if (productImage && color) {
            productImage.classList.add('opacity-40');
            setTimeout(() => {
                productImage.src = colorImages[color] || defaultImage;
                productImage.classList.remove('opacity-40');
            }, 120);
        }
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
