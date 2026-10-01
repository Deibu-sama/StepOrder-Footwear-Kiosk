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

<div class="flex items-end justify-between gap-4">
    <div>
        <p class="font-bold uppercase text-black/40">INVENTORY</p>
        <h1 class="text-4xl font-black">{{ $product ? 'Edit' : 'Add' }} Product</h1>
    </div>
    <a href="{{ url('/admin/products') }}" class="font-black underline">← Products</a>
</div>

<form method="POST" action="{{ $product ? url('/admin/products/'.$product['id']) : url('/admin/products') }}" class="mt-6 max-w-4xl space-y-6 rounded-3xl bg-white p-6">
    @csrf
    @if($product) @method('PUT') @endif

    <div class="grid gap-5 md:grid-cols-2">
        <label class="block font-black">
            NAME
            <input name="name" value="{{ old('name', $product['name'] ?? '') }}" class="mt-2 w-full rounded-xl border-2 border-black px-4 py-3">
        </label>

        <label class="block font-black">
            SKU
            <input name="sku" value="{{ old('sku', $product['sku'] ?? '') }}" class="mt-2 w-full rounded-xl border-2 border-black px-4 py-3">
        </label>

        <label class="block font-black">
            CATEGORY
            <select id="category_id" name="category_id" class="mt-2 w-full rounded-xl border-2 border-black px-4 py-3">
                @foreach($categories as $category)
                    <option value="{{ $category['id'] }}" data-name="{{ $category['name'] }}" @selected(($product['category_id'] ?? '') === $category['id'])>{{ $category['name'] }}</option>
                @endforeach
            </select>
            <input type="hidden" id="category_name" name="category_name" value="{{ old('category_name', $product['category_name'] ?? ($categories[0]['name'] ?? '')) }}">
        </label>

        <label class="block font-black">
            GENDER
            <select name="gender" class="mt-2 w-full rounded-xl border-2 border-black px-4 py-3">
                @foreach(['Unisex', 'Men', 'Women'] as $genderOption)
                    <option value="{{ $genderOption }}" @selected(($product['gender'] ?? 'Unisex') === $genderOption)>{{ $genderOption }}</option>
                @endforeach
            </select>
        </label>

        <label class="block font-black">
            REGULAR PRICE
            <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $product['price'] ?? 0) }}" class="mt-2 w-full rounded-xl border-2 border-black px-4 py-3">
        </label>

        <label class="block font-black">
            SALE PRICE
            <span class="text-xs font-bold text-black/40">(optional)</span>
            <input type="number" step="0.01" min="0" name="sale_price" value="{{ old('sale_price', $product['sale_price'] ?? '') }}" placeholder="e.g. 1999" class="mt-2 w-full rounded-xl border-2 border-black px-4 py-3">
        </label>
    </div>

    <label class="block font-black">
        DEFAULT IMAGE URL
        <input id="image_url" name="image_url" type="url" value="{{ old('image_url', $product['image_url'] ?? '') }}" placeholder="https://..." class="mt-2 w-full rounded-xl border-2 border-black px-4 py-3">
    </label>

    <div class="grid gap-6 md:grid-cols-2">
        <label class="block font-black">
            DESCRIPTION
            <textarea name="description" rows="5" class="mt-2 w-full rounded-xl border-2 border-black px-4 py-3">{{ old('description', $product['description'] ?? '') }}</textarea>
        </label>
        <div>
            <p class="font-black">IMAGE PREVIEW</p>
            <div class="mt-2 aspect-square max-w-xs overflow-hidden rounded-2xl border-2 border-black bg-stone-100">
                <img id="image_preview" src="{{ $product['image_url'] ?? '' }}" alt="Preview" class="h-full w-full object-cover {{ empty($product['image_url']) ? 'hidden' : '' }}">
                <div id="image_empty" class="grid h-full place-items-center p-6 text-center font-bold text-black/40 {{ !empty($product['image_url']) ? 'hidden' : '' }}">Paste an image URL to preview it.</div>
            </div>
        </div>
    </div>

    <div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-black">SIZE / COLOR STOCK</h2>
                <p class="text-sm font-bold text-black/50">Each row is one sellable variant.</p>
            </div>
            <button type="button" id="add-variant" class="rounded-xl bg-lime-400 px-5 py-3 font-black">+ ADD SIZE / COLOR</button>
        </div>
        <div id="variant-list" class="mt-4 space-y-3">
            @foreach($existingVariants as $index => $variant)
                <div class="variant-row grid gap-3 rounded-2xl border-2 border-black/10 bg-stone-50 p-4 md:grid-cols-[1fr_1.5fr_1fr_auto]">
                    <input name="variants[{{ $index }}][size]" value="{{ $variant['size'] ?? '' }}" placeholder="Size" class="variant-size rounded-xl border-2 border-black bg-white px-4 py-3 font-bold">
                    <input name="variants[{{ $index }}][color]" value="{{ $variant['color'] ?? '' }}" placeholder="Color" class="variant-color rounded-xl border-2 border-black bg-white px-4 py-3 font-bold">
                    <input name="variants[{{ $index }}][stock]" type="number" min="0" max="9999" value="{{ $variant['stock'] ?? 0 }}" placeholder="Stock" class="rounded-xl border-2 border-black bg-white px-4 py-3 font-bold">
                    <button type="button" class="remove-variant rounded-xl border-2 border-black bg-white px-4 py-3 font-black text-red-600">REMOVE</button>
                </div>
            @endforeach
        </div>
    </div>

    <div>
        <h2 class="text-2xl font-black">COLOR IMAGES</h2>
        <p class="text-sm font-bold text-black/50">Set an image for each color. The kiosk changes the product image when the customer switches colors.</p>
        <div id="color-image-list" class="mt-4 space-y-3">
            @foreach($existingColors as $color)
                <div class="color-image-row grid gap-3 md:grid-cols-[180px_1fr]" data-color-row="{{ $color }}">
                    <div class="flex items-center rounded-xl border-2 border-black bg-stone-50 px-4 py-3 font-black">{{ $color }}</div>
                    <input name="color_images[{{ $color }}]" value="{{ $existingColorImages[$color] ?? '' }}" type="url" placeholder="Image URL for {{ $color }}" class="rounded-xl border-2 border-black px-4 py-3">
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-2">
        <label class="flex items-center gap-3 rounded-2xl border-2 border-black/10 bg-stone-50 p-4 font-black">
            <input type="checkbox" name="is_top_pick" value="1" class="h-5 w-5" {{ old('is_top_pick', (bool)($product['is_top_pick'] ?? false)) ? 'checked' : '' }}>
            SHOW “TOP PICK” TAG
        </label>
        <label class="flex items-center gap-3 rounded-2xl border-2 border-black/10 bg-stone-50 p-4 font-black">
            <input type="checkbox" name="status" value="1" class="h-5 w-5" {{ old('status', ($product['status'] ?? 'active') === 'active') ? 'checked' : '' }}>
            PRODUCT IS ACTIVE
        </label>
    </div>

    <button class="rounded-2xl bg-black px-7 py-4 font-black text-white">SAVE PRODUCT</button>
</form>

<script>
    let variantIndex = {{ count($existingVariants) }};
    const colorImages = @json($existingColorImages);

    document.getElementById('category_id').addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        document.getElementById('category_name').value = option.dataset.name || option.text;
    });

    const imageUrl = document.getElementById('image_url');
    const imagePreview = document.getElementById('image_preview');
    const imageEmpty = document.getElementById('image_empty');

    function refreshImagePreview() {
        const value = imageUrl.value.trim();
        imagePreview.classList.toggle('hidden', !value);
        imageEmpty.classList.toggle('hidden', !!value);
        if (value) imagePreview.src = value;
    }

    function bindRemoveButtons() {
        document.querySelectorAll('.remove-variant').forEach(button => {
            button.onclick = () => {
                const rows = document.querySelectorAll('.variant-row');
                if (rows.length <= 1) { alert('Keep at least one size/color variant.'); return; }
                button.closest('.variant-row').remove();
                syncColorImageFields();
            };
        });
    }

    function currentColors() {
        return [...document.querySelectorAll('.variant-color')]
            .map(input => input.value.trim())
            .filter(Boolean)
            .filter((color, index, list) => list.indexOf(color) === index);
    }

    function syncColorImageFields() {
        const list = document.getElementById('color-image-list');
        const colors = currentColors();
        const existing = {};
        document.querySelectorAll('.color-image-row').forEach(row => {
            const color = row.dataset.colorRow;
            const input = row.querySelector('input');
            existing[color] = input ? input.value : '';
        });

        list.innerHTML = '';
        colors.forEach(color => {
            const row = document.createElement('div');
            row.className = 'color-image-row grid gap-3 md:grid-cols-[180px_1fr]';
            row.dataset.colorRow = color;
            const label = document.createElement('div');
            label.className = 'flex items-center rounded-xl border-2 border-black bg-stone-50 px-4 py-3 font-black';
            label.textContent = color;
            const input = document.createElement('input');
            input.name = 'color_images[' + color + ']';
            input.type = 'url';
            input.placeholder = 'Image URL for ' + color;
            input.value = existing[color] || colorImages[color] || '';
            input.className = 'rounded-xl border-2 border-black px-4 py-3';
            row.append(label, input);
            list.appendChild(row);
        });
    }

    document.getElementById('add-variant').addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'variant-row grid gap-3 rounded-2xl border-2 border-black/10 bg-stone-50 p-4 md:grid-cols-[1fr_1.5fr_1fr_auto]';
        row.innerHTML = '<input name="variants[' + variantIndex + '][size]" placeholder="Size" class="variant-size rounded-xl border-2 border-black bg-white px-4 py-3 font-bold">' +
            '<input name="variants[' + variantIndex + '][color]" placeholder="Color" class="variant-color rounded-xl border-2 border-black bg-white px-4 py-3 font-bold">' +
            '<input name="variants[' + variantIndex + '][stock]" type="number" min="0" max="9999" value="0" placeholder="Stock" class="rounded-xl border-2 border-black bg-white px-4 py-3 font-bold">' +
            '<button type="button" class="remove-variant rounded-xl border-2 border-black bg-white px-4 py-3 font-black text-red-600">REMOVE</button>';
        document.getElementById('variant-list').appendChild(row);
        variantIndex++;
        bindRemoveButtons();
        row.querySelector('.variant-color').addEventListener('input', syncColorImageFields);
        syncColorImageFields();
    });

    document.querySelectorAll('.variant-color').forEach(input => input.addEventListener('input', syncColorImageFields));
    bindRemoveButtons();
    refreshImagePreview();
    syncColorImageFields();
</script>
@endsection