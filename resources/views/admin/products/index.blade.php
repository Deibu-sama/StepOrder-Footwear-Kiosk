@extends('layouts.admin')

@section('content')
<div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
    <div>
        <p class="text-xs font-black uppercase tracking-[0.25em] text-black/40">CATALOG</p>
        <h1 class="mt-1 text-4xl font-black">Products</h1>
        <p class="mt-2 font-bold text-black/50">Manage products, pricing, stock variants, images, and kiosk visibility.</p>
    </div>

    <a href="{{ url('/admin/products/create') }}" class="rounded-2xl bg-black px-5 py-3 font-black text-white">+ ADD PRODUCT</a>
</div>

<form method="GET" action="/admin/products" class="mt-6 grid gap-3 rounded-3xl border border-black/10 bg-white p-4 lg:grid-cols-[1.5fr_1fr_1fr_1fr_auto]">
    <input name="q" value="{{ $q }}" placeholder="Search name or SKU..."
           class="rounded-2xl border-2 border-black px-4 py-3 font-bold">
    <input type="hidden" name="view" value="{{ $view }}">
    <input type="hidden" name="per_page" value="{{ $perPage }}">
    <input type="hidden" name="columns" value="{{ $columns }}">
    <input type="hidden" name="sort" value="{{ $sort }}">

    <select name="category" class="rounded-2xl border-2 border-black px-4 py-3 font-bold">
        <option value="">All categories</option>
        @foreach($categories as $cat)
            <option value="{{ $cat['id'] }}" @selected($category === $cat['id'])>{{ $cat['name'] }}</option>
        @endforeach
    </select>

    <select name="gender" class="rounded-2xl border-2 border-black px-4 py-3 font-bold">
        <option value="">All genders</option>
        <option value="Unisex" @selected($gender === 'Unisex')>Unisex</option>
        <option value="Men" @selected($gender === 'Men')>Men</option>
        <option value="Women" @selected($gender === 'Women')>Women</option>
    </select>

    <select name="status" class="rounded-2xl border-2 border-black px-4 py-3 font-bold">
        <option value="">All status</option>
        <option value="active" @selected($status === 'active')>Active</option>
        <option value="inactive" @selected($status === 'inactive')>Archived</option>
    </select>

    <button class="rounded-2xl bg-lime-300 px-5 py-3 font-black">FILTER</button>
</form>

<div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-3xl border border-black/10 bg-white p-5">
        <p class="text-xs font-black uppercase tracking-widest text-black/40">RESULTS</p>
        <p class="mt-1 text-3xl font-black">{{ number_format($products->total()) }}</p>
        <p class="mt-1 text-sm font-bold text-black/40">products match the current filters</p>
    </div>
    <div class="rounded-3xl border border-black/10 bg-white p-5">
        <p class="text-xs font-black uppercase tracking-widest text-black/40">VIEWING</p>
        <p class="mt-1 text-3xl font-black">{{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}</p>
        <p class="mt-1 text-sm font-bold text-black/40">items on this page</p>
    </div>
    <div class="rounded-3xl border border-black/10 bg-white p-5">
        <p class="text-xs font-black uppercase tracking-widest text-black/40">ACTIVE</p>
        <p class="mt-1 text-3xl font-black">{{ number_format(collect($products->items())->filter(fn($p) => ($p['status'] ?? 'active') === 'active')->count()) }}</p>
        <p class="mt-1 text-sm font-bold text-black/40">active on this page</p>
    </div>
    <div class="rounded-3xl border border-black/10 bg-lime-300 p-5">
        <p class="text-xs font-black uppercase tracking-widest text-black/60">DISPLAY</p>
        <p class="mt-1 text-3xl font-black">{{ ucfirst($view) }} · {{ $perPage }}/page</p>
        <p class="mt-1 text-sm font-bold text-black/60">Column layout: {{ $columns }}</p>
    </div>
</div>

<div class="mt-5 flex flex-col gap-3 rounded-3xl border border-black/10 bg-white p-4 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs font-black uppercase tracking-widest text-black/40">DISPLAY</span>
        <a href="{{ request()->fullUrlWithQuery(['view' => 'list', 'page' => 1]) }}"
           class="rounded-xl border-2 border-black px-4 py-2 text-sm font-black {{ $view === 'list' ? 'bg-black text-white' : 'bg-white' }}">
            ☷ LIST
        </a>
        <a href="{{ request()->fullUrlWithQuery(['view' => 'grid', 'page' => 1]) }}"
           class="rounded-xl border-2 border-black px-4 py-2 text-sm font-black {{ $view === 'grid' ? 'bg-black text-white' : 'bg-white' }}">
            ▦ CARDS
        </a>

        @if($view === 'grid')
            <span class="ml-2 text-xs font-black uppercase tracking-widest text-black/40">COLUMNS</span>
            @foreach([2,3,4] as $option)
                <a href="{{ request()->fullUrlWithQuery(['columns' => $option, 'page' => 1]) }}"
                   class="grid h-9 w-9 place-items-center rounded-xl border-2 border-black text-sm font-black {{ $columns === $option ? 'bg-lime-300' : 'bg-white' }}">
                    {{ $option }}
                </a>
            @endforeach
        @endif
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <label class="text-xs font-black uppercase tracking-widest text-black/40">SHOW</label>
        @foreach([10,20,50] as $option)
            <a href="{{ request()->fullUrlWithQuery(['per_page' => $option, 'page' => 1]) }}"
               class="rounded-xl border-2 border-black px-3 py-2 text-sm font-black {{ $perPage === $option ? 'bg-lime-300' : 'bg-white' }}">
                {{ $option }}
            </a>
        @endforeach

        <label class="ml-2 text-xs font-black uppercase tracking-widest text-black/40">SORT</label>
        <select onchange="window.location=this.value" class="rounded-xl border-2 border-black px-3 py-2 font-bold">
            @foreach([
                'name' => 'Name',
                'recent' => 'Recently updated',
                'price_asc' => 'Price: low → high',
                'price_desc' => 'Price: high → low',
                'stock_asc' => 'Stock: low → high',
                'stock_desc' => 'Stock: high → low',
            ] as $value => $label)
                <option value="{{ request()->fullUrlWithQuery(['sort' => $value, 'page' => 1]) }}" @selected($sort === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="mt-5">
    @if($view === 'grid')
        <div class="grid gap-4 {{ $columns === 2 ? 'lg:grid-cols-2' : ($columns === 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-3') }}">
            @forelse($products as $product)
                <article class="overflow-hidden rounded-3xl border-2 border-black/10 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="relative aspect-[4/3] overflow-hidden bg-stone-100">
                        @if(!empty($product['image_url']))
                            <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}" class="h-full w-full object-cover">
                        @else
                            <div class="grid h-full place-items-center font-black text-black/30">NO IMAGE</div>
                        @endif

                        <div class="absolute left-3 top-3 flex flex-wrap gap-1.5">
                            <span class="rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-black">{{ strtoupper($product['status'] ?? 'active') }}</span>
                            @if(!empty($product['sale_price']))
                                <span class="rounded-full bg-red-500 px-2.5 py-1 text-[10px] font-black text-white">SALE</span>
                            @endif
                            @if(!empty($product['is_top_pick']))
                                <span class="rounded-full bg-black px-2.5 py-1 text-[10px] font-black text-white">TOP PICK</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-black uppercase tracking-widest text-black/40">{{ $product['_category'] }}</p>
                                <h2 class="mt-1 text-xl font-black">{{ $product['name'] }}</h2>
                                <p class="mt-1 text-xs font-bold text-black/40">{{ $product['sku'] }} · {{ $product['gender'] ?? 'Unisex' }}</p>
                            </div>
                            <p class="shrink-0 text-lg font-black">
                                ₱{{ number_format($product['sale_price'] ?? $product['price'] ?? 0, 2) }}
                            </p>
                        </div>

                        <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                            <div class="rounded-xl bg-stone-100 p-2">
                                <p class="text-[10px] font-black uppercase text-black/40">Units</p>
                                <p class="mt-1 font-black">{{ $product['_units'] }}</p>
                            </div>
                            <div class="rounded-xl bg-stone-100 p-2">
                                <p class="text-[10px] font-black uppercase text-black/40">Variants</p>
                                <p class="mt-1 font-black">{{ $product['_variants'] }}</p>
                            </div>
                            <div class="rounded-xl bg-stone-100 p-2">
                                <p class="text-[10px] font-black uppercase text-black/40">Low/Out</p>
                                <p class="mt-1 font-black">{{ $product['_low'] }}/{{ $product['_out'] }}</p>
                            </div>
                        </div>

                        <div class="mt-4 flex gap-2">
                            <a href="{{ url('/admin/products/'.$product['id'].'/edit') }}" class="flex-1 rounded-xl border-2 border-black px-3 py-2.5 text-center text-sm font-black">EDIT</a>
                            <a href="{{ url('/products/'.$product['sku']) }}" target="_blank" class="rounded-xl bg-lime-300 px-3 py-2.5 text-sm font-black">VIEW</a>
                            @if(($product['status'] ?? 'active') === 'active')
                                <form method="POST" action="/admin/products/{{ $product['id'] }}" onsubmit="return confirm('Archive this product?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-xl bg-red-50 px-3 py-2.5 text-sm font-black text-red-600">ARCHIVE</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-black/10 bg-white p-12 text-center">
                    <p class="text-2xl font-black">No products found.</p>
                    <p class="mt-2 font-bold text-black/40">Add a product or clear your filters.</p>
                </div>
            @endforelse
        </div>
    @else
        <div class="overflow-hidden rounded-3xl border border-black/10 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-[1100px] w-full text-left">
                    <thead class="bg-stone-50 text-xs font-black uppercase tracking-widest text-black/50">
                        <tr>
                            <th class="px-5 py-4">Product</th>
                            <th class="px-5 py-4">Category</th>
                            <th class="px-5 py-4">Price</th>
                            <th class="px-5 py-4">Inventory</th>
                            <th class="px-5 py-4">Flags</th>
                            <th class="px-5 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/10">
                        @forelse($products as $product)
                            <tr class="hover:bg-stone-50">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-2xl border border-black/10 bg-stone-100">
                                            @if(!empty($product['image_url']))
                                                <img src="{{ $product['image_url'] }}" alt="" class="h-full w-full object-cover">
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-black">{{ $product['name'] }}</p>
                                            <p class="text-xs font-bold text-black/40">{{ $product['sku'] }} · {{ $product['gender'] ?? 'Unisex' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-bold">{{ $product['_category'] }}</td>
                                <td class="px-5 py-4">
                                    @if(!empty($product['sale_price']))
                                        <p class="font-black text-red-600">₱{{ number_format($product['sale_price'], 2) }}</p>
                                        <p class="text-xs font-bold text-black/40 line-through">₱{{ number_format($product['price'], 2) }}</p>
                                    @else
                                        <p class="font-black">₱{{ number_format($product['price'], 2) }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-black">{{ $product['_units'] }} units</p>
                                    <p class="text-xs font-bold {{ $product['_out'] ? 'text-red-600' : ($product['_low'] ? 'text-amber-600' : 'text-green-600') }}">
                                        {{ $product['_out'] }} out · {{ $product['_low'] }} low
                                    </p>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ ($product['status'] ?? 'active') === 'active' ? 'bg-green-100 text-green-700' : 'bg-stone-200 text-black/50' }}">
                                            {{ strtoupper($product['status'] ?? 'active') }}
                                        </span>
                                        @if(!empty($product['is_top_pick']))
                                            <span class="rounded-full bg-black px-2.5 py-1 text-[10px] font-black text-white">TOP PICK</span>
                                        @endif
                                        @if(!empty($product['sale_price']))
                                            <span class="rounded-full bg-red-100 px-2.5 py-1 text-[10px] font-black text-red-600">SALE</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ url('/admin/products/'.$product['id'].'/edit') }}" class="rounded-xl border-2 border-black px-3 py-2 text-sm font-black">EDIT</a>
                                        @if(($product['status'] ?? 'active') === 'active')
                                            <form method="POST" action="/admin/products/{{ $product['id'] }}" onsubmit="return confirm('Archive this product?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-xl bg-red-50 px-3 py-2 text-sm font-black text-red-600">ARCHIVE</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-12 text-center"><p class="text-2xl font-black">No products found.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="mt-5 flex flex-col gap-3 rounded-3xl border border-black/10 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm font-bold text-black/50">
            Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ $products->total() }} products
        </p>
        <div class="flex items-center gap-2">
            @if($products->onFirstPage())
                <span class="rounded-xl border-2 border-black/20 px-4 py-2 text-sm font-black text-black/30">← PREV</span>
            @else
                <a href="{{ $products->previousPageUrl() }}" class="rounded-xl border-2 border-black px-4 py-2 text-sm font-black">← PREV</a>
            @endif

            <span class="rounded-xl bg-black px-4 py-2 text-sm font-black text-white">
                PAGE {{ $products->currentPage() }} / {{ $products->lastPage() }}
            </span>

            @if($products->hasMorePages())
                <a href="{{ $products->nextPageUrl() }}" class="rounded-xl border-2 border-black px-4 py-2 text-sm font-black">NEXT →</a>
            @else
                <span class="rounded-xl border-2 border-black/20 px-4 py-2 text-sm font-black text-black/30">NEXT →</span>
            @endif
        </div>
    </div>
</div>

</div>
@endsection