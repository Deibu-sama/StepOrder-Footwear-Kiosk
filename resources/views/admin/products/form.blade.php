@extends('layouts.admin')

@section('content')
@php
    $existingVariants = $product['variants'] ?? [];
    if (!$existingVariants) {
        $existingVariants = [
            ['size' => '39', 'color' => 'Black', 'stock' => 5],
            ['size' => '40', 'color' => 'Black', 'stock' => 5],
            ['size' => '41', 'color' => 'Black', 'stock' => 5],
        ];
    }

    $existingColorImages = $product['color_images'] ?? [];
    $existingColors = collect($existingVariants)->pluck('color')->filter()->unique()->values();
@endphp

<div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
    <div>
        <p class="text-xs font-black uppercase tracking-[0.25em] text-black/40">CATALOG / PRODUCT</p>
        <h1 class="mt-1 text-4xl font-black">{{ $product ? 'Edit Product' : 'Add Product' }}</h1>
        <p class="mt-2 max-w-2xl font-bold text-black/50">
            Product images are URL-only. Nothing is uploaded to Railway, and no Firebase Storage is required.
        </p>
    </div>
    <a href="{{ url('/admin/products') }}" class="font-black underline">← Products</a>
</div>

<form method="POST"
      action="{{ $product ? url('/admin/products/'.$product['id']) : url('/admin/products') }}"
      class="mt-6 space-y-6">
    @csrf
    @if($product)
        @method('PUT')
    @endif

    <section class="rounded-3xl border border-black/10 bg-white p-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-black/40">BASIC INFORMATION</p>
                <h2 class="mt-1 text-2xl font-black">Product details</h2>
            </div>
            <span class="rounded-full bg-lime-100 px-3 py-1 text-xs font-black text-lime-700">URL-ONLY MEDIA</span>
        </div>

        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <label class="block font-black">
                PRODUCT NAME
                <input name="name"
                       value="{{ old('name', $product['name'] ?? '') }}"
                       placeholder="e.g. Classic Runner"
                       class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3">
            </label>

            <label class="block font-black">
                SKU / PRODUCT URL
                <input name="sku"
                       value="{{ old('sku', $product['sku'] ?? '') }}"
                       placeholder="e.g. STP-019"
                       pattern="[A-Za-z0-9][A-Za-z0-9._-]*"
                       title="Use letters, numbers, dots, underscores, or hyphens."
                       class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3">
                <span class="mt-1 block text-xs font-bold text-black/40">
                    Unique SKU used in the customer URL, e.g. /products/STP-019.
                </span>
            </label>

            <label class="block font-black">
                CATEGORY
                <select id="category_id"
                        name="category_id"
                        class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3">
                    @foreach($categories as $category)
                        <option value="{{ $category['id'] }}"
                                data-name="{{ $category['name'] }}"
                                @selected(($product['category_id'] ?? '') === $category['id'])>
                            {{ $category['name'] }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden"
                       id="category_name"
                       name="category_name"
                       value="{{ old('category_name', $product['category_name'] ?? ($categories[0]['name'] ?? '')) }}">
            </label>

            <label class="block font-black">
                GENDER
                <select name="gender" class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3">
                    @foreach(['Unisex', 'Men', 'Women'] as $genderOption)
                        <option value="{{ $genderOption }}" @selected(($product['gender'] ?? 'Unisex') === $genderOption)>
                            {{ $genderOption }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="block font-black">
                REGULAR PRICE
                <input type="number"
                       step="0.01"
                       min="0"
                       name="price"
                       value="{{ old('price', $product['price'] ?? 0) }}"
                       class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3">
            </label>

            <label class="block font-black">
                SALE PRICE
                <span class="text-xs font-bold text-black/40">(optional)</span>
                <input type="number"
                       step="0.01"
                       min="0"
                       name="sale_price"
                       value="{{ old('sale_price', $product['sale_price'] ?? '') }}"
                       placeholder="Leave blank for no sale"
                       class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3">
                <span class="mt-1 block text-xs font-bold text-black/40">Must be lower than the regular price.</span>
            </label>
        </div>

        <div class="mt-5">
            <label class="block font-black">
                DESCRIPTION
                <textarea name="description"
                          rows="4"
                          placeholder="Describe the footwear..."
                          class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3">{{ old('description', $product['description'] ?? '') }}</textarea>
            </label>
        </div>
    </section>

    <section class="rounded-3xl border border-black/10 bg-white p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-black/40">MEDIA</p>
                <h2 class="mt-1 text-2xl font-black">Product images</h2>
                <p class="mt-1 text-sm font-bold text-black/50">Paste public image URLs only.</p>
            </div>
        </div>

        <label class="mt-5 block font-black">
            DEFAULT IMAGE URL
            <input id="image_url"
                   name="image_url"
                   type="url"
                   value="{{ old('image_url', $product['image_url'] ?? '') }}"
                   placeholder="https://..."
                   class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3">
        </label>

        <div class="mt-5 grid gap-6 lg:grid-cols-[1fr_320px]">
            <div class="rounded-2xl bg-[#fff3c9] p-5">
                <p class="font-black">WHY URL-ONLY?</p>
                <p class="mt-2 text-sm font-bold text-black/50">
                    The kiosk stores only the link. This keeps the Laravel deployment lightweight and avoids Firebase Storage costs.
                </p>
                <p class="mt-3 text-xs font-bold text-black/40">
                    Recommended sources: your own public image host, a school web server, or another stable public CDN.
                </p>
            </div>

            <div>
                <p class="text-xs font-black uppercase tracking-widest text-black/40">PREVIEW</p>
                <div class="mt-2 aspect-square overflow-hidden rounded-2xl border-2 border-black bg-stone-100">
                    <img id="image_preview"
                         src="{{ $product['image_url'] ?? '' }}"
                         alt=""
                         class="h-full w-full object-cover {{ empty($product['image_url']) ? 'hidden' : '' }}">
                    <div id="image_empty"
                         class="grid h-full place-items-center p-6 text-center font-bold text-black/30 {{ !empty($product['image_url']) ? 'hidden' : '' }}">
                        Paste an image URL to preview it.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-black/10 bg-white p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-black/40">INVENTORY</p>
                <h2 class="mt-1 text-2xl font-black">Size / color stock</h2>
                <p class="mt-1 text-sm font-bold text-black/50">Each row is one sellable variant.</p>
            </div>

            <button type="button"
                    id="add-variant"
                    class="rounded-2xl bg-lime-300 px-5 py-3 font-black">
                + ADD SIZE / COLOR
            </button>
        </div>

        <div class="mt-5 hidden grid-cols-[1fr_1.4fr_1fr_auto] gap-3 px-4 text-[10px] font-black uppercase tracking-widest text-black/40 md:grid">
            <span>Size</span>
            <span>Color</span>
            <span>Stock</span>
            <span></span>
        </div>

        <div id="variant-list" class="mt-3 space-y-3">
            @foreach($existingVariants as $index => $variant)
                <div class="variant-row grid gap-3 rounded-2xl border-2 border-black/10 bg-stone-50 p-4 md:grid-cols-[1fr_1.4fr_1fr_auto]">
                    <input name="variants[{{ $index }}][size]"
                           value="{{ $variant['size'] ?? '' }}"
                           placeholder="42"
                           class="variant-size rounded-xl border-2 border-black bg-white px-4 py-3 font-bold">

                    <input name="variants[{{ $index }}][color]"
                           value="{{ $variant['color'] ?? '' }}"
                           placeholder="Black"
                           class="variant-color rounded-xl border-2 border-black bg-white px-4 py-3 font-bold">

                    <input name="variants[{{ $index }}][stock]"
                           type="number"
                           min="0"
                           max="9999"
                           value="{{ $variant['stock'] ?? 0 }}"
                           placeholder="0"
                           class="rounded-xl border-2 border-black bg-white px-4 py-3 font-bold">

                    <button type="button"
                            class="remove-variant rounded-xl border-2 border-black bg-white px-4 py-3 font-black text-red-600">
                        REMOVE
                    </button>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-3xl border border-black/10 bg-white p-6">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-black/40">COLOR MEDIA</p>
            <h2 class="mt-1 text-2xl font-black">Color-specific images</h2>
            <p class="mt-1 text-sm font-bold text-black/50">
                Optional. When the customer changes color in the kiosk, the main image changes too.
            </p>
        </div>

        <div id="color-image-list" class="mt-5 space-y-3">
            @foreach($existingColors as $color)
                <div class="color-image-row grid gap-3 md:grid-cols-[180px_1fr_120px]"
                     data-color-row="{{ $color }}">
                    <div class="flex items-center rounded-xl border-2 border-black bg-stone-50 px-4 py-3 font-black">
                        {{ $color }}
                    </div>

                    <input name="color_images[{{ $color }}]"
                           value="{{ $existingColorImages[$color] ?? '' }}"
                           type="url"
                           placeholder="Image URL for {{ $color }}"
                           class="color-image-input rounded-xl border-2 border-black bg-white px-4 py-3">

                    <div class="color-preview aspect-square overflow-hidden rounded-xl border-2 border-black bg-stone-100">
                        @if(!empty($existingColorImages[$color]))
                            <img src="{{ $existingColorImages[$color] }}" alt="" class="h-full w-full object-cover">
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 rounded-2xl bg-stone-50 p-4 text-sm font-bold text-black/50">
            Colors are generated automatically from the variants above. Add a new variant color and its image field will appear here.
        </div>
    </section>

    <section class="grid gap-3 md:grid-cols-2">
        <label class="flex items-center gap-3 rounded-3xl border border-black/10 bg-white p-5 font-black">
            <input type="checkbox"
                   name="is_top_pick"
                   value="1"
                   class="h-5 w-5"
                   {{ old('is_top_pick', (bool)($product['is_top_pick'] ?? false)) ? 'checked' : '' }}>
            <span>
                <span class="block">SHOW ⭐ TOP PICK</span>
                <span class="mt-1 block text-xs font-bold text-black/40">Feature this product in the kiosk.</span>
            </span>
        </label>

        <label class="flex items-center gap-3 rounded-3xl border border-black/10 bg-white p-5 font-black">
            <input type="checkbox"
                   name="status"
                   value="1"
                   class="h-5 w-5"
                   {{ old('status', ($product['status'] ?? 'active') === 'active') ? 'checked' : '' }}>
            <span>
                <span class="block">PRODUCT IS ACTIVE</span>
                <span class="mt-1 block text-xs font-bold text-black/40">Inactive products are hidden from the kiosk.</span>
            </span>
        </label>
    </section>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
        <a href="{{ url('/admin/products') }}" class="rounded-2xl border-2 border-black px-7 py-4 text-center font-black">CANCEL</a>
        <button class="rounded-2xl bg-black px-8 py-4 font-black text-white">
            {{ $product ? 'SAVE CHANGES' : 'CREATE PRODUCT' }}
        </button>
    </div>
</form>

<script>
    let variantIndex = {{ count($existingVariants) }};
    const colorImages = @json($existingColorImages);

    const categoryId = document.getElementById('category_id');
    const categoryName = document.getElementById('category_name');

    categoryId?.addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        categoryName.value = option.dataset.name || option.text;
    });

    const imageUrl = document.getElementById('image_url');
    const imagePreview = document.getElementById('image_preview');
    const imageEmpty = document.getElementById('image_empty');

    imageUrl?.addEventListener('input', () => {
        const value = imageUrl.value.trim();
        imagePreview.classList.toggle('hidden', !value);
        imageEmpty.classList.toggle('hidden', !!value);
        if (value) imagePreview.src = value;
    });

    function currentColors() {
        return [...document.querySelectorAll('.variant-color')]
            .map(input => input.value.trim())
            .filter(Boolean)
            .filter((color, index, list) => list.indexOf(color) === index);
    }

    function collectExistingColorImages() {
        const values = {};

        document.querySelectorAll('.color-image-row').forEach(row => {
            const color = row.dataset.colorRow;
            const input = row.querySelector('.color-image-input');
            values[color] = input?.value?.trim() || '';
        });

        return values;
    }

    function renderColorImageFields() {
        const list = document.getElementById('color-image-list');
        const colors = currentColors();
        const existing = collectExistingColorImages();

        list.innerHTML = '';

        colors.forEach(color => {
            const row = document.createElement('div');
            row.className = 'color-image-row grid gap-3 md:grid-cols-[180px_1fr_120px]';
            row.dataset.colorRow = color;

            const label = document.createElement('div');
            label.className = 'flex items-center rounded-xl border-2 border-black bg-stone-50 px-4 py-3 font-black';
            label.textContent = color;

            const input = document.createElement('input');
            input.name = 'color_images[' + color + ']';
            input.type = 'url';
            input.placeholder = 'Image URL for ' + color;
            input.value = existing[color] || colorImages[color] || '';
            input.className = 'color-image-input rounded-xl border-2 border-black bg-white px-4 py-3';

            const preview = document.createElement('div');
            preview.className = 'color-preview aspect-square overflow-hidden rounded-xl border-2 border-black bg-stone-100';

            if (input.value) {
                const image = document.createElement('img');
                image.src = input.value;
                image.className = 'h-full w-full object-cover';
                preview.appendChild(image);
            }

            input.addEventListener('input', () => {
                preview.innerHTML = '';
                if (!input.value.trim()) return;

                const image = document.createElement('img');
                image.src = input.value.trim();
                image.className = 'h-full w-full object-cover';
                preview.appendChild(image);
            });

            row.append(label, input, preview);
            list.appendChild(row);
        });
    }

    function bindVariantActions() {
        document.querySelectorAll('.remove-variant').forEach(button => {
            button.onclick = () => {
                const rows = document.querySelectorAll('.variant-row');

                if (rows.length <= 1) {
                    alert('Keep at least one size/color variant.');
                    return;
                }

                button.closest('.variant-row').remove();
                renderColorImageFields();
            };
        });

        document.querySelectorAll('.variant-color').forEach(input => {
            input.oninput = renderColorImageFields;
        });
    }

    document.getElementById('add-variant')?.addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'variant-row grid gap-3 rounded-2xl border-2 border-black/10 bg-stone-50 p-4 md:grid-cols-[1fr_1.4fr_1fr_auto]';

        row.innerHTML =
            '<input name="variants[' + variantIndex + '][size]" placeholder="42" class="variant-size rounded-xl border-2 border-black bg-white px-4 py-3 font-bold">' +
            '<input name="variants[' + variantIndex + '][color]" placeholder="Black" class="variant-color rounded-xl border-2 border-black bg-white px-4 py-3 font-bold">' +
            '<input name="variants[' + variantIndex + '][stock]" type="number" min="0" max="9999" value="0" class="rounded-xl border-2 border-black bg-white px-4 py-3 font-bold">' +
            '<button type="button" class="remove-variant rounded-xl border-2 border-black bg-white px-4 py-3 font-black text-red-600">REMOVE</button>';

        document.getElementById('variant-list').appendChild(row);
        variantIndex++;

        bindVariantActions();
        renderColorImageFields();
    });

    bindVariantActions();
    renderColorImageFields();
</script>
@endsection