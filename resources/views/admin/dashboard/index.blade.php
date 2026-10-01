@extends('layouts.admin')

@section('content')
@php
    $settings = app(\App\Services\SettingsService::class)->all();
    $currentStaff = session('steporder_admin', []);
    $isAdmin = ($currentStaff['role'] ?? '') === 'admin';
@endphp

<div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
    <div>
        <p class="text-xs font-black uppercase tracking-[0.25em] text-black/40">OPERATIONS OVERVIEW</p>
        <h1 class="mt-1 text-4xl font-black tracking-tight sm:text-5xl">Good day, {{ ($currentStaff['name'] ?? 'Administrator') }}.</h1>
        <p class="mt-2 max-w-2xl font-bold text-black/50">
            Monitor sales, orders, products, and inventory from one place.
        </p>
    </div>

    <div class="flex flex-wrap gap-2">
        @if($isAdmin)
            <a href="{{ url('/admin/products/create') }}" class="rounded-2xl bg-black px-5 py-3 font-black text-white">+ Add Product</a>
        @endif
        <a href="{{ url('/admin/pos') }}" class="rounded-2xl border-2 border-black bg-lime-300 px-5 py-3 font-black">Open POS</a>
    </div>
</div>

<div class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-3xl border border-black/10 bg-white p-6">
        <p class="text-xs font-black uppercase tracking-widest text-black/40">ACTIVE PRODUCTS</p>
        <p class="mt-2 text-4xl font-black">{{ count($activeProducts) }}</p>
        <p class="mt-2 text-sm font-bold text-black/40">{{ count($products) }} total records</p>
    </div>

    <div class="rounded-3xl border border-black/10 bg-white p-6">
        <p class="text-xs font-black uppercase tracking-widest text-black/40">PENDING ORDERS</p>
        <p class="mt-2 text-4xl font-black">{{ count($pendingOrders) }}</p>
        <a href="{{ url('/admin/pos') }}" class="mt-2 inline-block text-sm font-black underline">Open cashier →</a>
    </div>

    <div class="rounded-3xl border border-black/10 bg-white p-6">
        <p class="text-xs font-black uppercase tracking-widest text-black/40">PAID ORDERS</p>
        <p class="mt-2 text-4xl font-black">{{ count($paidOrders) }}</p>
        <p class="mt-2 text-sm font-bold text-black/40">Paid or released</p>
    </div>

    <div class="rounded-3xl border-2 border-black bg-lime-300 p-6">
        <p class="text-xs font-black uppercase tracking-widest">TODAY'S SALES</p>
        <p class="mt-2 text-4xl font-black">₱{{ number_format($todaySales, 2) }}</p>
        <p class="mt-2 text-sm font-bold">{{ $todayTransactions }} completed sale(s)</p>
    </div>
</div>

<div class="mt-5 grid gap-4 lg:grid-cols-3">
    @if($isAdmin)
        <a href="{{ url('/admin/inventory?status=low') }}" class="rounded-3xl border-2 border-amber-300 bg-amber-50 p-5 transition hover:-translate-y-0.5">
            <div class="flex items-center justify-between">
                <p class="font-black">LOW STOCK</p>
                <span class="rounded-full bg-amber-200 px-3 py-1 text-xs font-black">≤ {{ $settings['low_stock_threshold'] }}</span>
            </div>
            <p class="mt-3 text-4xl font-black">{{ count($lowStockVariants) }}</p>
            <p class="mt-1 font-bold text-amber-800/60">Variants need attention</p>
        </a>

        <a href="{{ url('/admin/inventory?status=out') }}" class="rounded-3xl border-2 border-red-200 bg-red-50 p-5 transition hover:-translate-y-0.5">
            <div class="flex items-center justify-between">
                <p class="font-black">OUT OF STOCK</p>
                <span class="rounded-full bg-red-200 px-3 py-1 text-xs font-black">0</span>
            </div>
            <p class="mt-3 text-4xl font-black">{{ count($outOfStockVariants) }}</p>
            <p class="mt-1 font-bold text-red-800/60">Variants unavailable</p>
        </a>
    @else
        <a href="{{ url('/admin/orders?status=pending') }}" class="rounded-3xl border-2 border-amber-300 bg-amber-50 p-5 transition hover:-translate-y-0.5">
            <div class="flex items-center justify-between">
                <p class="font-black">WAITING FOR PAYMENT</p>
                <span class="rounded-full bg-amber-200 px-3 py-1 text-xs font-black">POS</span>
            </div>
            <p class="mt-3 text-4xl font-black">{{ count($pendingOrders) }}</p>
            <p class="mt-1 font-bold text-amber-800/60">Orders awaiting cashier action</p>
        </a>

        <a href="{{ url('/admin/orders?status=paid') }}" class="rounded-3xl border-2 border-blue-200 bg-blue-50 p-5 transition hover:-translate-y-0.5">
            <div class="flex items-center justify-between">
                <p class="font-black">READY TO RELEASE</p>
                <span class="rounded-full bg-blue-200 px-3 py-1 text-xs font-black">PAID</span>
            </div>
            <p class="mt-3 text-4xl font-black">{{ count($paidOrders) }}</p>
            <p class="mt-1 font-bold text-blue-800/60">Orders awaiting release</p>
        </a>
    @endif

    <div class="rounded-3xl border border-black/10 bg-white p-5">
        <p class="font-black">TODAY</p>
        <div class="mt-3 flex items-end justify-between">
            <div>
                <p class="text-3xl font-black">{{ count($todayOrders) }}</p>
                <p class="text-sm font-bold text-black/40">orders created</p>
            </div>
            <a href="{{ url('/admin/orders') }}" class="rounded-xl border-2 border-black px-4 py-2 text-sm font-black">View orders</a>
        </div>
    </div>
</div>

<div class="mt-7 grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
    <section class="overflow-hidden rounded-3xl border border-black/10 bg-white">
        <div class="flex items-center justify-between border-b border-black/10 px-6 py-5">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-black/40">LATEST ACTIVITY</p>
                <h2 class="mt-1 text-2xl font-black">Recent Orders</h2>
            </div>
            <a href="{{ url('/admin/orders') }}" class="text-sm font-black underline">View all</a>
        </div>

        <div class="divide-y divide-black/10">
            @if(count($recentOrders) > 0)
                @foreach($recentOrders as $order)
                    @php
                        $status = $order['status'] ?? 'pending';
                        $badge = match ($status) {
                            'paid' => 'bg-blue-100 text-blue-700',
                            'completed' => 'bg-green-100 text-green-700',
                            'cancelled' => 'bg-red-100 text-red-700',
                            default => 'bg-amber-100 text-amber-700',
                        };
                    @endphp

                    <a href="{{ url('/admin/orders/'.$order['id']) }}" class="flex items-center justify-between gap-4 px-6 py-4 hover:bg-stone-50">
                        <div class="min-w-0">
                            <p class="truncate font-black">#{{ $order['order_number'] ?? $order['id'] }}</p>
                            <p class="truncate text-sm font-bold text-black/40">{{ count($order['items'] ?? []) }} item(s)</p>
                        </div>
                        <div class="text-right">
                            <p class="font-black">₱{{ number_format($order['total'] ?? 0, 2) }}</p>
                            <span class="mt-1 inline-block rounded-full px-2.5 py-1 text-[10px] font-black {{ $badge }}">{{ strtoupper($status) }}</span>
                        </div>
                    </a>
                @endforeach
            @else
                <div class="p-10 text-center font-bold text-black/40">No orders yet.</div>
            @endif
        </div>
    </section>

    <section class="rounded-3xl border border-black/10 bg-white">
        <div class="border-b border-black/10 px-6 py-5">
            <p class="text-xs font-black uppercase tracking-widest text-black/40">SALES SNAPSHOT</p>
            <h2 class="mt-1 text-2xl font-black">Top Products</h2>
        </div>

        <div class="divide-y divide-black/10">
            @if(count($topProducts) > 0)
                @foreach($topProducts as $index => $product)
                    <div class="flex items-center justify-between gap-4 px-6 py-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-lime-300 text-sm font-black">{{ $index + 1 }}</span>
                            <div class="min-w-0">
                                <p class="truncate font-black">{{ $product['name'] ?? 'Unknown product' }}</p>
                                <p class="text-xs font-bold text-black/40">{{ $product['units'] ?? 0 }} unit(s)</p>
                            </div>
                        </div>
                        <p class="shrink-0 font-black">₱{{ number_format($product['sales'] ?? 0, 2) }}</p>
                    </div>
                @endforeach
            @else
                <div class="p-8 text-center text-sm font-bold text-black/40">Sales data will appear after paid orders.</div>
            @endif
        </div>
    </section>
</div>
@endsection
