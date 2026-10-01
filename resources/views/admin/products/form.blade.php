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
@endphp

<div class="flex items-end justify-between gap-4">
    <div>
        <p class="font-bold uppercase text-black/40">INVENTORY</p>
        <h1 class="text-4xl font-black">{{ $product ? 'Edit' : 'Add' }} Product</h1>
    </div>
    <a href="{{ url('/admin/products') }}" class="font-black underline">← Products</a>
</div>

<form method="POST"
      action="{{ $product ? url('/admin/products/'.$product['id']) : url('/admin/products') }}"
      class="mt-6 max-w-4xl space-y-6 rounded-3xl bg-white p-6">
    @csrf
    @if($product)
        @method('PUT')
    @endif

    <div class="grid gap-5 md:grid-cols-2">
        <label class="block font-black">
            NAME
            <input name="name" value="{{ old('name', $product['name'] ?? '') }}"
                   class="mt-2 w-full rounded-xl border-2 border-black px-4 py-3">
        </label>

        <label class="block font-black">
            SKU
            <input name="sku" value="{{ old('sku', $product['sku'] ?? '') }}"
                   class="mt-2 w-full rounded-xl border-2 border-black px-4 py-3">
        </label>

        <label class="block font-black">
            CATEGORY
            <select id="category_id" name="category_id"
                    class="mt-2 w-full rounded-xl border-2 border-black px-4 py-3">
                @foreach($categories as $category)
                    <option value="{{ $category['id'] }}"
                            data-name="{{ $category['name'] }}"
                            @selected(($product['category_id'] ?? '') === $category['id'])>
                        {{ $category['name'] }}
                    </option>
                @endforeach
            </select>
            <input type="hidden" id="category_name" name="category_name"
                   value="{{ old('category_name', $product['category_name'] ?? ($categories[0]['name'] ?? '')) }}">
        </label>

        <label class="block font-black">
            REGULAR PRICE
            <input type="number" step="0.01" min="0" name="price"
                   value="{{ old('price', $product['price'] ?? 0) }}"
                   class="mt-2 w-full rounded-xl border-2 border-black px-4 py-3">
        </label>

        <label class="block font-black">
            SALE PRICE
            <span class="text-xs font-bold text-black/40">(leave blank for no sale)</span>
            <input type="number" step="0.01" min="0" name="sale_price"
                   value="{{ old('sale_price', $product['sale_price'] ?? '') }}"
                   placeholder="e.g. 1999"
                   class="mt-2 w-full rounded-xl border-2 border-black px-4 py-3">
        </label>
    </div>

    <label class="block font-black">
        IMAGE URL
        <input id="image_url" name="image_url" type="url"
               value="{{ old('image_url', $product['image_url'] ?? '') }}"
               placeholder="https://..."
               class="mt-2 w-full rounded-xl border-2 border-black px-4 py-3">
    </label>

    <div class="rounded-2xl border-2 border-red-100 bg-red-50 p-4">            <div>
                <p class="text-sm font-bold text-black/50">Set a sale price lower than the regular price to show the SALE badge on the kiosk.</p>
            </div></div>

    <div class="grid gap-6 md:grid-cols-2">
        <label class="block font-black">
            DESCRIPTION
            <textarea name="description" rows="5"
                      class="mt-2 w-full rounded-xl border-2 border-black px-4 py-3">{{ old('description', $product['description'] ?? '') }}</textarea>
        </label>

        <div>
            <p class="font-black">IMAGE PREVIEW</p>
            <div class="mt-2 aspect-square max-w-xs overflow-hidden rounded-2xl border-2 border-black bg-stone-100">
                <img id="image_preview" src="{{ $product['image_url'] ?? '' }}"
                     alt="Preview" class="h-full w-full object-cover {{ empty($product['image_url']) ? 'hidden' : '' }}">
                <div id="image_empty" class="grid h-full place-items-center p-6 text-center font-bold text-black/40 {{ !empty($product['image_url']) ? 'hidden' : '' }}">
                    Paste an image URL to preview it.
                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-black">SIZE / COLOR STOCK</h2>
                <p class="text-sm font-bold text-black/50">Each row is one sellable footwear variant.</p>
            </div>
            <button type="button" id="add-variant"
                    class="rounded-xl bg-lime-400 px-5 py-3 font-black">
                + ADD SIZE / COLOR
            </button>
        </div>

        <div id="variant-list" class="mt-4 space-y-3">
            @foreach($existingVariants as $index => $variant)
                <div class="variant-row grid gap-3 rounded-2xl border-2 border-black/10 bg-stone-50 p-4 md:grid-cols-[1fr_1.5fr_1fr_auto]">
                    <input name="variants[{{ $index }}][size]" value="{{ $variant['size'] ?? '' }}" placeholder="Size (e.g. 42)"
                           class="rounded-xl border-2 border-black bg-white px-4 py-3 font-bold">
                    <input name="variants[{{ $index }}][color]" value="{{ $variant['color'] ?? '' }}" placeholder="Color (e.g. Black)"
                           class="rounded-xl border-2 border-black bg-white px-4 py-3 font-bold">
                    <input name="variants[{{ $index }}][stock]" type="number" min="0" max="9999" value="{{ $variant['stock'] ?? 0 }}" placeholder="Stock"
                           class="rounded-xl border-2 border-black bg-white px-4 py-3 font-bold">
                    <button type="button" class="remove-variant rounded-xl border-2 border-black bg-white px-4 py-3 font-black text-red-600">REMOVE</button>
                </div>
            @endforeach
        </div>
    </div>

    <label class="flex items-center gap-3 rounded-2xl bg-stone-50 p-4 font-black">
        <input type="checkbox" name="status" value="1"
               class="h-5 w-5"
               {{ old('status', ($product['status'] ?? 'active') === 'active') ? 'checked' : '' }}>
        PRODUCT IS ACTIVE / AVAILABLE
    </label>

    <button class="rounded-2xl bg-black px-7 py-4 font-black text-white">
        SAVE PRODUCT
    </button>
</form>

<script>
    let variantIndex = {{ count($existingVariants) }};

    document.getElementById('category_id').addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        document.getElementById('category_name').value = option.dataset.name || option.text;
    });

    const imageUrl = document.getElementById('image_url');
    const imagePreview = document.getElementById('image_preview');
    const imageEmpty = document.getElementById('image_empty');

    function refreshImagePreview() {
        const value = imageUrl.value.trim();
        if (!value) {
            imagePreview.classList.add('hidden');
            imageEmpty.classList.remove('hidden');
            return;
        }

        imagePreview.src = value;
        imagePreview.classList.remove('hidden');
        imageEmpty.classList.add('hidden');
    }

    imageUrl.addEventListener('input', refreshImagePreview);

    function bindRemoveButtons() {
        document.querySelectorAll('.remove-variant').forEach(button => {
            button.onclick = () => {
                const rows = document.querySelectorAll('.variant-row');
                if (rows.length <= 1) {
                    alert('Keep at least one size/color variant.');
                    return;
                }
                button.closest('.variant-row').remove();
            };
        });
    }

    document.getElementById('add-variant').addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'variant-row grid gap-3 rounded-2xl border-2 border-black/10 bg-stone-50 p-4 md:grid-cols-[1fr_1.5fr_1fr_auto]';
        row.innerHTML = `
            <input name="variants[${variantIndex}][size]" placeholder="Size (e.g. 42)" class="rounded-xl border-2 border-black bg-white px-4 py-3 font-bold">
            <input name="variants[${variantIndex}][color]" placeholder="Color (e.g. Black)" class="rounded-xl border-2 border-black bg-white px-4 py-3 font-bold">
            <input name="variants[${variantIndex}][stock]" type="number" min="0" max="9999" value="0" placeholder="Stock" class="rounded-xl border-2 border-black bg-white px-4 py-3 font-bold">
            <button type="button" class="remove-variant rounded-xl border-2 border-black bg-white px-4 py-3 font-black text-red-600">REMOVE</button>
        `;
        document.getElementById('variant-list').appendChild(row);
        variantIndex++;
        bindRemoveButtons();
    });

    bindRemoveButtons();
    refreshImagePreview();
</script>
@endsection
