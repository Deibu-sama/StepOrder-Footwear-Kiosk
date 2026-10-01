@extends('layouts.kiosk')

@section('content')
@php($settings = app(\App\Services\SettingsService::class)->all())
<main class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <a href="{{ url('/') }}" class="font-black uppercase text-black/40">← Start Screen</a>
            <p class="mt-2 text-xs font-black uppercase tracking-[0.25em] text-black/50">FOOTWEAR ORDERING KIOSK</p>
            <h1 class="mt-1 text-4xl font-black sm:text-5xl">Choose your step.</h1>
        </div>
        <a href="{{ url('/cart') }}" class="hidden rounded-2xl border-2 border-black bg-lime-300 px-5 py-3 font-black md:block">VIEW CART</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-[270px_1fr]">
        <aside class="filter-scrollbar h-fit max-h-[calc(100vh-7rem)] overflow-y-auto rounded-3xl border-2 border-black bg-white p-4 lg:sticky lg:top-24">
            <form id="filter-form" method="GET" action="{{ url('/menu') }}" class="space-y-5">
                <div>
                    <p class="text-xs font-black uppercase tracking-widest text-black/40">QUICK FILTERS</p>
                    <div class="mt-3 grid gap-2">
                        <a href="{{ url('/menu') }}" class="rounded-2xl border-2 border-black px-4 py-3 font-black {{ !$filter && !$selectedCategory && !$gender && !$priceRange ? 'bg-lime-300' : 'bg-white' }}">ALL FOOTWEAR</a>
                        @if($settings['show_top_picks'])
                            <a href="{{ url('/menu?filter=top_pick') }}" class="rounded-2xl border-2 border-black px-4 py-3 font-black {{ $filter === 'top_pick' ? 'bg-black text-white' : 'bg-white' }}">⭐ TOP PICKS</a>
                        @endif
                        @if($settings['show_sale_filter'])
                            <a href="{{ url('/menu?filter=sale') }}" class="rounded-2xl border-2 border-black px-4 py-3 font-black {{ $filter === 'sale' ? 'bg-red-500 text-white' : 'bg-white' }}">🏷️ ON SALE</a>
                        @endif
                    </div>
                </div>

                <div class="border-t-2 border-black/10 pt-5">
                    <label class="text-xs font-black uppercase tracking-widest text-black/40">SEARCH</label>
                    <input name="q"
                           value="{{ $search }}"
                           placeholder="Shoe, category..."
                           class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
                </div>

                <div class="border-t-2 border-black/10 pt-5">
                    <p class="text-xs font-black uppercase tracking-widest text-black/40">CATEGORY</p>
                    <div class="mt-3 space-y-2">
                        <a href="{{ url('/menu') }}"
                           class="block rounded-2xl border-2 border-black px-4 py-3 font-black {{ !$selectedCategory ? 'bg-lime-300' : 'bg-white' }}">
                            ALL CATEGORIES
                        </a>
                        @foreach($categories as $category)
                            <a href="{{ url('/menu?category='.$category['id']) }}"
                               class="block rounded-2xl border-2 border-transparent px-4 py-3 font-black transition {{ $selectedCategory === $category['id'] ? 'border-black bg-lime-300' : 'hover:bg-stone-100' }}">
                                {{ $category['name'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                @if($settings['show_gender_filter'])
                    <div class="border-t-2 border-black/10 pt-5">
                        <label class="text-xs font-black uppercase tracking-widest text-black/40">GENDER</label>
                        <select name="gender"
                                class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold"
                                data-auto-filter>
                            <option value="">All genders</option>
                            @foreach(['Unisex', 'Men', 'Women'] as $genderOption)
                                <option value="{{ $genderOption }}" @selected($gender === $genderOption)>{{ $genderOption }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if($settings['show_price_filter'])
                    <div class="border-t-2 border-black/10 pt-5">
                        <label class="text-xs font-black uppercase tracking-widest text-black/40">PRICE RANGE</label>
                        <select name="price_range"
                                class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold"
                                data-auto-filter>
                            <option value="">Any price</option>
                            <option value="under_1000" @selected($priceRange === 'under_1000')>Under {{ $settings['currency_symbol'] }}1,000</option>
                            <option value="1000_1999" @selected($priceRange === '1000_1999')>{{ $settings['currency_symbol'] }}1,000–{{ $settings['currency_symbol'] }}1,999</option>
                            <option value="2000_2999" @selected($priceRange === '2000_2999')>{{ $settings['currency_symbol'] }}2,000–{{ $settings['currency_symbol'] }}2,999</option>
                            <option value="3000_plus" @selected($priceRange === '3000_plus')>{{ $settings['currency_symbol'] }}3,000+</option>
                        </select>
                    </div>
                @endif

                <input type="hidden" name="filter" value="{{ $filter }}">
            </form>
        </aside>

        <section>
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <p class="font-black text-black/50">{{ count($products) }} item(s)</p>
                    @if($filter === 'top_pick')
                        <span class="mt-1 inline-block rounded-full bg-black px-3 py-1 text-xs font-black text-white">⭐ TOP PICKS</span>
                    @elseif($filter === 'sale')
                        <span class="mt-1 inline-block rounded-full bg-red-500 px-3 py-1 text-xs font-black text-white">ON SALE</span>
                    @endif
                </div>
                <a href="{{ url('/cart') }}" class="rounded-2xl border-2 border-black bg-lime-300 px-4 py-2 text-sm font-black md:hidden">CART</a>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @forelse($products as $product)
                    @php
                        $productOut = count($product['variants'] ?? []) === 0 || collect($product['variants'])->every(fn($v) => (int)($v['stock'] ?? 0) <= 0);
                        $isSale = $product['_is_sale'] ?? false;
                        $salePrice = $product['_sale_price'] ?? null;
                        $regularPrice = (float)($product['price'] ?? 0);
                        $topPick = (bool)($product['_top_pick'] ?? false);
                        $discount = $isSale ? round((($regularPrice - $salePrice) / $regularPrice) * 100) : 0;
                    @endphp

                    <a href="{{ $productOut ? 'javascript:void(0)' : url('/products/'.$product['id']) }}"
                       class="relative overflow-hidden rounded-3xl border-2 border-black bg-[#d7e84e] p-3 transition {{ $productOut ? 'cursor-not-allowed opacity-60 grayscale' : 'hover:-translate-y-1' }}">
                        @if($isSale)
                            <span class="absolute left-5 top-5 z-10 rounded-full bg-red-500 px-3 py-1 text-xs font-black text-white shadow">{{ $discount }}% OFF</span>
                        @endif
                        @if($topPick)
                            <span class="absolute right-5 top-5 z-10 rounded-full bg-black px-3 py-1 text-xs font-black text-white shadow">⭐ TOP PICK</span>
                        @endif
                        @if($productOut)
                            <span class="absolute bottom-5 left-5 z-10 rounded-full bg-red-600 px-3 py-1 text-xs font-black text-white">OUT OF STOCK</span>
                        @endif
                        <div class="aspect-square overflow-hidden rounded-2xl bg-white">
                            <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}" class="h-full w-full object-cover">
                        </div>
                        <h2 class="mt-4 text-lg font-black uppercase sm:text-xl">{{ $product['name'] }}</h2>
                        @if($isSale)
                            <div class="mt-1 flex items-end gap-2">
                                <p class="text-lg font-black text-red-600">{{ $settings['currency_symbol'] }}{{ number_format($salePrice, 2) }}</p>
                                <p class="text-sm font-bold text-black/40 line-through">{{ $settings['currency_symbol'] }}{{ number_format($regularPrice, 2) }}</p>
                            </div>
                        @else
                            <p class="mt-1 font-black">₱{{ number_format($regularPrice, 2) }}</p>
                        @endif
                        <div class="mt-2 flex items-center justify-between gap-2 text-xs font-bold uppercase opacity-60">
                            <span>{{ $product['category_name'] }}</span>
                            <span>{{ $product['gender'] ?? 'Unisex' }}</span>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full rounded-3xl border-2 border-black bg-white p-10 text-center">
                        <p class="text-2xl font-black">No footwear found.</p>
                        <p class="mt-2 font-bold text-black/50">Try another category, gender, or price range.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</main>

<script>
    const filterForm = document.getElementById('filter-form');

    document.querySelectorAll('[data-auto-filter]').forEach(select => {
        select.addEventListener('change', () => filterForm.submit());
    });

    const searchInput = filterForm.querySelector('[name="q"]');
    searchInput?.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            filterForm.submit();
        }
    });
</script>
@endsection
