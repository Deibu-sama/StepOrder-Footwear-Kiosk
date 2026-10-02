@extends('layouts.admin')

@section('content')
<div class="flex items-end justify-between gap-4">
    <div>
        <p class="text-xs font-black uppercase tracking-[0.25em] text-black/40">CATALOG STRUCTURE</p>
        <h1 class="mt-1 text-4xl font-black">{{ $category ? 'Edit Category' : 'Add Category' }}</h1>
        <p class="mt-2 font-bold text-black/50">Images are URL-only; nothing is uploaded to Railway or Firestore Storage.</p>
    </div>
    <a href="/admin/categories" class="font-black underline">← Categories</a>
</div>

<form method="POST"
      action="{{ $category ? url('/admin/categories/'.$category['id']) : url('/admin/categories') }}"
      class="mt-6 max-w-3xl space-y-6">
    @csrf
    @if($category)
        @method('PUT')
    @endif

    <div class="rounded-3xl border border-black/10 bg-white p-6">
        <div class="grid gap-5 md:grid-cols-2">
            <label class="block font-black">
                CATEGORY NAME
                <input name="name"
                       value="{{ old('name', $category['name'] ?? '') }}"
                       placeholder="e.g. Running Shoes"
                       class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3">
            </label>

            <label class="block font-black">
                IMAGE URL
                <input id="image_url"
                       name="image_url"
                       type="url"
                       value="{{ old('image_url', $category['image_url'] ?? '') }}"
                       placeholder="https://..."
                       class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3">
            </label>
        </div>

        <label class="mt-5 block font-black">
            DESCRIPTION
            <textarea name="description"
                      rows="4"
                      placeholder="Short description..."
                      class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3">{{ old('description', $category['description'] ?? '') }}</textarea>
        </label>

        @if($category)
            <label class="mt-5 flex items-center gap-3 rounded-2xl bg-stone-50 p-4 font-black">
                <input type="checkbox"
                       name="active"
                       value="1"
                       class="h-5 w-5"
                       {{ old('active', ($category['active'] ?? true)) ? 'checked' : '' }}>
                CATEGORY IS VISIBLE ON THE KIOSK
            </label>
        @endif

        <div class="mt-6 grid gap-6 md:grid-cols-2">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-black/40">IMAGE PREVIEW</p>
                <div class="mt-2 aspect-[16/8] overflow-hidden rounded-2xl border-2 border-black bg-stone-100">
                    <img id="image_preview"
                         src="{{ $category['image_url'] ?? '' }}"
                         class="h-full w-full object-cover {{ empty($category['image_url']) ? 'hidden' : '' }}"
                         alt="">
                    <div id="image_empty"
                         class="grid h-full place-items-center p-5 text-center font-bold text-black/30 {{ !empty($category['image_url']) ? 'hidden' : '' }}">
                        Paste an image URL to preview the category image.
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-[#fff3c9] p-5">
                <p class="font-black">URL-ONLY IMAGE STORAGE</p>
                <p class="mt-2 text-sm font-bold text-black/50">
                    Use an externally hosted image URL. StepOrder stores only the link, which keeps the Railway and Firestore footprint small.
                </p>
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <button class="rounded-2xl bg-black px-7 py-4 font-black text-white">
                SAVE CATEGORY
            </button>
            <a href="/admin/categories" class="rounded-2xl border-2 border-black px-7 py-4 text-center font-black">
                CANCEL
            </a>
        </div>
    </div>
</form>

<script>
    const imageUrl = document.getElementById('image_url');
    const imagePreview = document.getElementById('image_preview');
    const imageEmpty = document.getElementById('image_empty');

    imageUrl?.addEventListener('input', () => {
        const value = imageUrl.value.trim();
        imagePreview.classList.toggle('hidden', !value);
        imageEmpty.classList.toggle('hidden', !!value);
        if (value) imagePreview.src = value;
    });
</script>
@endsection