@extends('layouts.admin')

@section('content')
<div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
    <div>
        <p class="text-xs font-black uppercase tracking-[0.25em] text-black/40">STOCK CONTROL</p>
        <h1 class="mt-1 text-4xl font-black">Inventory</h1>
        <p class="mt-2 font-bold text-black/50">Monitor size/color stock without uploading a single image file.</p>
    </div>

    <a href="{{ url('/admin/products/create') }}" class="rounded-2xl bg-black px-5 py-3 font-black text-white">+ ADD PRODUCT</a>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-3">
    <div class="rounded-3xl bg-white p-5">
        <p class="text-xs font-black uppercase tracking-widest text-black/40">TOTAL UNITS</p>
        <p class="mt-2 text-4xl font-black">{{ $totalUnits }}</p>
    </div>
    <div class="rounded-3xl border-2 border-amber-200 bg-amber-50 p-5">
        <p class="text-xs font-black uppercase tracking-widest text-amber-700/70">LOW STOCK VARIANTS</p>
        <p class="mt-2 text-4xl font-black">{{ $lowStock }}</p>
    </div>
    <div class="rounded-3xl border-2 border-red-200 bg-red-50 p-5">
        <p class="text-xs font-black uppercase tracking-widest text-red-700/70">OUT OF STOCK VARIANTS</p>
        <p class="mt-2 text-4xl font-black">{{ $outOfStock }}</p>
    </div>
</div>

<form method="GET" action="{{ url('/admin/inventory') }}" class="mt-6 grid gap-3 rounded-3xl border border-black/10 bg-white p-4 md:grid-cols-[1fr_240px_auto]">
    <input name="q" value="{{ $q }}" placeholder="Search product or SKU..."
           class="rounded-2xl border-2 border-black px-4 py-3 font-bold">
    <select name="status" class="rounded-2xl border-2 border-black px-4 py-3 font-bold">
        <option value="">All inventory</option>
        <option value="healthy" @selected($status === 'healthy')>Healthy</option>
        <option value="low" @selected($status === 'low')>Low stock</option>
        <option value="out" @selected($status === 'out')>Out of stock</option>
    </select>
    <button class="rounded-2xl bg-lime-300 px-5 py-3 font-black">FILTER</button>
</form>

<div class="mt-5 overflow-hidden rounded-3xl border border-black/10 bg-white">
    <div class="overflow-x-auto">
        <table class="min-w-[920px] w-full text-left">
            <thead class="bg-stone-50 text-xs font-black uppercase tracking-widest text-black/50">
                <tr>
                    <th class="px-5 py-4">Product</th>
                    <th class="px-5 py-4">Category</th>
                    <th class="px-5 py-4">Variants</th>
                    <th class="px-5 py-4">Units</th>
                    <th class="px-5 py-4">Stock health</th>
                    <th class="px-5 py-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black/10">
                @forelse($rows as $row)
                    <tr class="hover:bg-stone-50">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-12 w-12 overflow-hidden rounded-2xl bg-stone-100">
                                    @if($row['image_url'])
                                        <img src="{{ $row['image_url'] }}" alt="" class="h-full w-full object-cover">
                                    @endif
                                </div>
                                <div>
                                    <p class="font-black">{{ $row['name'] }}</p>
                                    <p class="text-xs font-bold text-black/40">{{ $row['sku'] }} · {{ $row['gender'] }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 font-bold">{{ $row['category'] }}</td>
                        <td class="px-5 py-4 font-black">{{ $row['variants'] }}</td>
                        <td class="px-5 py-4 font-black">{{ $row['units'] }}</td>
                        <td class="px-5 py-4">
                            @if($row['status'] === 'out')
                                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-black text-red-700">OUT</span>
                            @elseif($row['status'] === 'low')
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700">{{ $row['low'] }} LOW</span>
                            @else
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-700">HEALTHY</span>
                            @endif
                            <p class="mt-1 text-xs font-bold text-black/40">{{ $row['out'] }} out of stock</p>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ url('/admin/products/'.$row['id'].'/edit') }}"
                               class="rounded-xl border-2 border-black px-4 py-2 text-sm font-black">
                                MANAGE
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-12 text-center">
                            <p class="text-2xl font-black">No inventory records found.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection