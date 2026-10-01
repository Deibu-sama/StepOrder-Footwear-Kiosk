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

<form method="GET" action="{{ url('/admin/products') }}" class="mt-6 grid gap-3 rounded-3xl border border-black/10 bg-white p-4 lg:grid-cols-[1.5fr_1fr_1fr_1fr_auto]">
    <input name="q" value="{{ $q }}" placeholder="Search name or SKU..."
           class="rounded-2xl border-2 border-black px-4 py-3 font-bold">

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

<div class="mt-5 overflow-hidden rounded-3xl border border-black/10 bg-white">
    <div class="overflow-x-auto">
        <table class="min-w-[980px] w-full text-left">
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
                @if(count($products) > 0)
                @foreach($products as $product)
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
                                <a href="{{ url('/admin/products/'.$product['id'].'/edit') }}"
                                   class="rounded-xl border-2 border-black px-3 py-2 text-sm font-black">EDIT</a>

                                @if(($product['status'] ?? 'active') === 'active')
                                    <form method="POST" action="{{ url('/admin/products/'.$product['id']) }}"
                                          onsubmit="return confirm('Archive this product? It will be hidden from the kiosk but preserved for existing orders.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-xl bg-red-50 px-3 py-2 text-sm font-black text-red-600">ARCHIVE</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            @else
                    <tr>
                        <td colspan="6" class="p-12 text-center">
                            <p class="text-2xl font-black">No products found.</p>
                            <p class="mt-2 font-bold text-black/40">Add a product or clear your filters.</p>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection