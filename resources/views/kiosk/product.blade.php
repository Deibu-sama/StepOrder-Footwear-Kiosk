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
                        <div class="flex items-center justify-between">
                            <label class="font-black">QUANTITY</label>
                            <span class="text-xs font-black text-black/40">MAX <span id="quantity-max">1</span></span>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <button type="button"
                                    id="minus-ten"
                                    class="hidden h-12 rounded-xl border-2 border-black bg-white px-4 font-black">
                                −10
                            </button>

                            <button type="button"
                                    id="minus-five"
                                    class="hidden h-12 rounded-xl border-2 border-black bg-white px-4 font-black">
                                −5
                            </button>

                            <button type="button"
                                    id="minus"
                                    class="h-12 w-12 rounded-xl border-2 border-black bg-white text-2xl font-black">
                                −
                            </button>

                            <input type="hidden" id="quantity" name="quantity" value="1" min="1" max="1">
                            <span id="quantity-label"
                                  class="grid h-12 min-w-16 place-items-center rounded-xl border-2 border-black bg-[#fff3c9] px-4 text-lg font-black">
                                1
                            </span>

                            <button type="button"
                                    id="plus"
                                    class="h-12 w-12 rounded-xl border-2 border-black bg-white text-2xl font-black">
                                +
                            </button>

                            <button type="button"
                                    id="plus-five"
                                    class="h-12 rounded-xl border-2 border-black bg-white px-4 font-black">
                                +5
                            </button>

                            <button type="button"
                                    id="plus-ten"
                                    class="h-12 rounded-xl border-2 border-black bg-white px-4 font-black">
                                +10
                            </button>
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

@if(count($relatedProducts))
<section class="mx-auto max-w-6xl px-5 pb-12">
    <div class="border-t-2 border-black/10 pt-10">
        <p class="text-xs font-black uppercase tracking-[0.25em] text-black/40">YOU MAY ALSO LIKE</p>
        <div class="mt-2 flex items-end justify-between gap-4">
            <h2 class="text-3xl font-black">Related footwear</h2>
            <a href="{{ url('/menu?category='.$product['category_id']) }}" class="font-black text-black/50">SEE CATEGORY →</a>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach($relatedProducts as $related)
                @php
                    $relatedRegular = (float)($related['price'] ?? 0);
                    $relatedSale = $related['_sale_price'] ?? null;
                    $relatedDiscount = $related['_is_sale'] && $relatedRegular > 0
                        ? round((($relatedRegular - $relatedSale) / $relatedRegular) * 100)
                        : 0;
                @endphp

                <a href="{{ url('/products/'.$related['id']) }}"
                   class="relative overflow-hidden rounded-3xl border-2 border-black bg-[#d7e84e] p-3 transition hover:-translate-y-1">
                    @if($related['_is_sale'])
                        <span class="absolute left-5 top-5 z-10 rounded-full bg-red-500 px-2.5 py-1 text-[10px] font-black text-white">
                            {{ $relatedDiscount }}% OFF
                        </span>
                    @endif

                    @if($related['_top_pick'])
                        <span class="absolute right-5 top-5 z-10 rounded-full bg-black px-2.5 py-1 text-[10px] font-black text-white">
                            ⭐ TOP PICK
                        </span>
                    @endif

                    <div class="aspect-square overflow-hidden rounded-2xl bg-white">
                        <img src="{{ $related['image_url'] }}"
                             alt="{{ $related['name'] }}"
                             class="h-full w-full object-cover">
                    </div>

                    <h3 class="mt-3 text-sm font-black uppercase sm:text-base">
                        {{ $related['name'] }}
                    </h3>

                    @if($related['_is_sale'])
                        <div class="mt-1 flex items-center gap-2">
                            <span class="font-black text-red-600">₱{{ number_format($relatedSale, 2) }}</span>
                            <span class="text-xs font-bold text-black/40 line-through">₱{{ number_format($relatedRegular, 2) }}</span>
                        </div>
                    @else
                        <p class="mt-1 font-black">₱{{ number_format($relatedRegular, 2) }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@if(!$allOut)
<script>
    const variants = @json($variants);
    const colorInputs = document.querySelectorAll('.color-option');
    const sizeInputs = document.querySelectorAll('.size-option');
    const quantity = document.getElementById('quantity');
    const addButton = document.getElementById('add-button');
    const stockSummary = document.getElementById('stock-summary');
    const quantityLabel = document.getElementById('quantity-label');
    const quantityMax = document.getElementById('quantity-max');
    const minusFive = document.getElementById('minus-five');
    const minusTen = document.getElementById('minus-ten');
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
        quantityMax.textContent = selectedStock;
        if (Number(quantity.value) > selectedStock) quantity.value = selectedStock;
        if (Number(quantity.value) < 1) quantity.value = 1;
        quantityLabel.textContent = quantity.value;

        addButton.disabled = !selected || selectedStock <= 0;
        stockSummary.textContent = totalForColor > 0 ? totalForColor + ' total in this color' : 'Out of stock';
    }

    colorInputs.forEach(input => input.addEventListener('change', refreshVariants));
    sizeInputs.forEach(input => input.addEventListener('change', refreshVariants));

    function setQuantity(nextValue) {
        const max = Number(quantity.max || 1);
        const value = Math.max(1, Math.min(max, Number(nextValue || 1)));
        quantity.value = value;
        quantityLabel.textContent = value;
        minusFive.classList.toggle('hidden', value <= 5);
        minusTen.classList.toggle('hidden', value <= 10);
    }

    document.getElementById('minus').addEventListener('click', () => {
        setQuantity(Number(quantity.value || 1) - 1);
    });

    document.getElementById('plus').addEventListener('click', () => {
        setQuantity(Number(quantity.value || 1) + 1);
    });

    minusFive.addEventListener('click', () => {
        setQuantity(Number(quantity.value || 1) - 5);
    });

    minusTen.addEventListener('click', () => {
        setQuantity(Number(quantity.value || 1) - 10);
    });

    document.getElementById('plus-five').addEventListener('click', () => {
        setQuantity(Number(quantity.value || 1) + 5);
    });

    document.getElementById('plus-ten').addEventListener('click', () => {
        setQuantity(Number(quantity.value || 1) + 10);
    });

    refreshVariants();
</script>
@endif
@endsection
