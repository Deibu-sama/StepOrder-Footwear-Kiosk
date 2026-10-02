@extends('layouts.admin')

@section('content')
<div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
    <div>
        <p class="text-xs font-black uppercase tracking-[0.25em] text-black/40">CATALOG STRUCTURE</p>
        <h1 class="mt-1 text-4xl font-black">Categories</h1>
        <p class="mt-2 font-bold text-black/50">Organize footwear into simple kiosk navigation groups.</p>
    </div>
    <a href="{{ url('/admin/categories/create') }}" class="rounded-2xl bg-black px-5 py-3 font-black text-white">+ ADD CATEGORY</a>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @if(count($categories) > 0)
                @foreach($categories as $category)
        <article class="group overflow-hidden rounded-3xl border border-black/10 bg-white">
            <div class="relative aspect-[16/8] bg-stone-100">
                @if(!empty($category['image_url']))
                    <img src="{{ $category['image_url'] }}" alt="{{ $category['name'] }}" class="h-full w-full object-cover">
                @else
                    <div class="grid h-full place-items-center text-4xl font-black text-black/10">STEP</div>
                @endif

                <span class="absolute right-4 top-4 rounded-full px-3 py-1 text-[10px] font-black {{ ($category['active'] ?? true) ? 'bg-green-100 text-green-700' : 'bg-black/10 text-black/50' }}">
                    {{ ($category['active'] ?? true) ? 'ACTIVE' : 'HIDDEN' }}
                </span>
            </div>

            <div class="p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-black">{{ $category['name'] }}</h2>
                        <p class="mt-1 text-xs font-bold uppercase tracking-widest text-black/40">{{ $category['slug'] ?? '' }}</p>
                    </div>
                    <div class="rounded-2xl bg-lime-100 px-3 py-2 text-right">
                        <p class="text-xl font-black">{{ $category['product_count'] }}</p>
                        <p class="text-[10px] font-black uppercase">Products</p>
                    </div>
                </div>

                <p class="mt-4 min-h-10 text-sm font-bold text-black/50">{{ $category['description'] ?? 'No description.' }}</p>

                <div class="mt-5 flex gap-2">
                    <a href="{{ url('/admin/categories/'.$category['id'].'/edit') }}"
                       class="flex-1 rounded-xl border-2 border-black px-4 py-3 text-center font-black">
                        EDIT
                    </a>

                    @if(($category['active'] ?? true))
                        <form method="POST" action="/admin/categories/{{ $category['id'] }}" class="flex-1"
                              onsubmit="return confirm('Hide this category from the kiosk?')">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="name" value="{{ $category['name'] }}">
                            <input type="hidden" name="description" value="{{ $category['description'] ?? '' }}">
                            <input type="hidden" name="image_url" value="{{ $category['image_url'] ?? '' }}">
                            <input type="hidden" name="active" value="0">
                            <button class="w-full rounded-xl bg-stone-100 px-4 py-3 font-black">HIDE</button>
                        </form>
                    @endif
                </div>
            </div>
        </article>
    @endforeach
            @else
        <div class="col-span-full rounded-3xl border border-black/10 bg-white p-12 text-center">
            <p class="text-2xl font-black">No categories yet.</p>
            <p class="mt-2 font-bold text-black/40">Create your first category to organize the kiosk.</p>
        </div>
    @endif
</div>
@endsection