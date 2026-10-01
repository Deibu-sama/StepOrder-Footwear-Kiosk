@extends('layouts.kiosk')

@section('content')
<main class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <a href="{{ url('/') }}" class="font-black uppercase text-black/40">← Start Screen</a>
            <p class="mt-2 text-xs font-black uppercase tracking-[0.25em] text-black/50">FOOTWEAR ORDERING KIOSK</p>
            <h1 class="mt-1 text-4xl font-black sm:text-5xl">Choose your step.</h1>
        </div>
        <a href="{{ url('/cart') }}" class="hidden rounded-2xl border-2 border-black bg-lime-300 px-5 py-3 font-black md:block">VIEW CART</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-[240px_1fr]">
        <aside class="h-fit rounded-3xl border-2 border-black bg-white p-4 lg:sticky lg:top-24">
            <form method="GET" action="{{ url('/menu') }}" class="space-y-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-widest text-black/40">FILTERS</p>
                    <div class="mt-3 grid gap-2">
                        <a href="{{ url('/menu') }}" class="rounded-2xl border-2 border-black px-4 py-3 font-black {{ !$filter && !$selectedCategory ? 'bg-lime-300' : 'bg-white' }}">ALL FOOTWEAR</a>
                        <a href="{{ url('/menu?filter=most_bought') }}" class="rounded-2xl border-2 border-black px-4 py-3 font-black {{ $filter === 'most_bought' ? 'bg-black text-white' : 'bg-white' }}">🔥 MOST BOUGHT</a>
                        <a href="{{ url('/menu?filter=sale') }}" class="rounded-2xl border-2 border-black px-4 py-3 font-black {{ $filter === 'sale' ? 'bg-red-500 text-white' : 'bg-white' }}">🏷️ ON SALE</a>
                    </div>
                </div>

                <div class="border-t-2 border-black/10 pt-4">
                    <p class="text-xs font-black uppercase tracking-widest text-black/40">CATEGORIES</p>
                    <div class="mt-3 space-y-2">
                        @foreach($categories as $category)
                            <a href="{{ url('/menu?category='.$category['id']) }}"
                               class="block rounded-2xl px-4 py-3 font-black {{ $selectedCategory === $category['id'] ? 'bg-lime-300 border-2 border-black' : 'border-2 border-transparent hover:bg-stone-100' }}">
                                {{ $category['name'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="border-t-2 border-black/10 pt-4">
                    <label class="text-xs font-black uppercase tracking-widest text-black/40">SEARCH</label>
                    <input name="q" value="{{ $search }}" placeholder="Shoe, category..."
                           class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
                    <button class="mt-2 w-full rounded-2xl bg-black px-4 py-3 font-black text-white">APPLY SEARCH</button>
                </div>
            </form>
        </aside>

        <section>
            <div class="mb-4 flex items-center justify-between">
                <p class="font-black text-black/50">{{ count($products) }} item(s)</p>
                @if($filter === 'most_bought')
                    <span class="rounded-full bg-black px-4 py-2 text-xs font-black text-white">MOST BOUGHT</span>
                @elseif($filter === 'sale')
                    <span class="rounded-full bg-red-500 px-4 py-2 text-xs font-black text-white">ON SALE</span>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @forelse($products as $product)
                    @php
                        $productOut = count($product['variants'] ?? []) === 0 || collect($product['variants'])->every(fn($v) => (int)($v['stock'] ?? 0) <= 0);
                        $isSale = $product['_is_sale'] ?? false;
                        $salePrice = $product['_sale_price'] ?? null;
                        $regularPrice = (float)($product['price'] ?? 0);
                        $soldCount = (int)($product['_sold_count'] ?? 0);
                        $mostBought = (bool)($product['_most_bought'] ?? false);
                        $discount = $isSale ? round((($regularPrice - $salePrice) / $regularPrice) * 100) : 0;
                    @endphp

                    <a href="{{ $productOut ? 'javascript:void(0)' : url('/products/'.$product['id']) }}"
                       class="relative overflow-hidden rounded-3xl border-2 border-black bg-[#d7e84e] p-3 transition {{ $productOut ? 'cursor-not-allowed opacity-60 grayscale' : 'hover:-translate-y-1' }}">
                        @if($isSale)
                            <span class="absolute left-5 top-5 z-10 rounded-full bg-red-500 px-3 py-1 text-xs font-black text-white shadow">{{ $discount }}% OFF</span>
                        @endif

                        @if($mostBought)
                            <span class="absolute right-5 top-5 z-10 rounded-full bg-black px-3 py-1 text-xs font-black text-white shadow">🔥 MOST BOUGHT</span>
                        @endif

                        @if($productOut)
                            <span class="absolute left-5 bottom-5 z-10 rounded-full bg-red-600 px-3 py-1 text-xs font-black text-white">OUT OF STOCK</span>
                        @endif

                        <div class="aspect-square overflow-hidden rounded-2xl bg-white">
                            <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}" class="h-full w-full object-cover">
                        </div>

                        <h2 class="mt-4 text-lg font-black uppercase sm:text-xl">{{ $product['name'] }}</h2>

                        @if($isSale)
                            <div class="mt-1 flex items-end gap-2">
                                <p class="text-lg font-black text-red-600">₱{{ number_format($salePrice, 2) }}</p>
                                <p class="text-sm font-bold text-black/40 line-through">₱{{ number_format($regularPrice, 2) }}</p>
                            </div>
                        @else
                            <p class="mt-1 font-black">₱{{ number_format($regularPrice, 2) }}</p>
                        @endif

                        <p class="mt-2 text-xs font-bold uppercase opacity-60">{{ $product['category_name'] }}</p>
                    </a>
                @empty
                    <div class="col-span-full rounded-3xl border-2 border-black bg-white p-10 text-center">
                        <p class="text-2xl font-black">No footwear found.</p>
                        <p class="mt-2 font-bold text-black/50">Try another category or filter.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</main>
@endsection
