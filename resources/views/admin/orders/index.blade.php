@extends('layouts.admin')

@section('content')
<div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
    <div>
        <p class="text-xs font-black uppercase tracking-[0.25em] text-black/40">TRANSACTIONS</p>
        <h1 class="mt-1 text-4xl font-black">Orders</h1>
        <p class="mt-2 font-bold text-black/50">Search, audit, and manage every kiosk transaction.</p>
    </div>
    <a href="{{ url('/admin/pos') }}" class="rounded-2xl bg-black px-5 py-3 font-black text-white">OPEN POS</a>
</div>

<form method="GET" action="{{ url('/admin/orders') }}" class="mt-6 grid gap-3 rounded-3xl border border-black/10 bg-white p-4 md:grid-cols-[1fr_220px_auto]">
    <input name="q" value="{{ $q }}" placeholder="Search order number or customer..."
           class="rounded-2xl border-2 border-black px-4 py-3 font-bold">

    <select name="status" class="rounded-2xl border-2 border-black px-4 py-3 font-bold">
        <option value="">All statuses</option>
        @foreach(['pending','paid','completed','cancelled'] as $option)
            <option value="{{ $option }}" @selected($status === $option)>{{ strtoupper($option) }}</option>
        @endforeach
    </select>

    <button class="rounded-2xl bg-lime-300 px-5 py-3 font-black">FILTER</button>
</form>

<div class="mt-5 overflow-hidden rounded-3xl border border-black/10 bg-white">
    <div class="overflow-x-auto">
        <table class="min-w-[900px] w-full text-left">
            <thead class="bg-stone-50 text-xs font-black uppercase tracking-widest text-black/50">
                <tr>
                    <th class="px-5 py-4">Order</th>
                    <th class="px-5 py-4">Items</th>
                    <th class="px-5 py-4">Total</th>
                    <th class="px-5 py-4">Status</th>
                    <th class="px-5 py-4">Created</th>
                    <th class="px-5 py-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black/10">
                @if(count($orders) > 0)
                @foreach($orders as $order)
                    @php
                        $statusValue = $order['status'] ?? 'pending';
                        $statusClass = match($statusValue) {
                            'paid' => 'bg-blue-100 text-blue-700',
                            'completed' => 'bg-green-100 text-green-700',
                            'cancelled' => 'bg-red-100 text-red-700',
                            default => 'bg-amber-100 text-amber-700',
                        };
                        $customerName = trim((string)($order['customer_name'] ?? ''));
                    @endphp
                    <tr class="hover:bg-stone-50">
                        <td class="px-5 py-4">
                            <p class="font-black">#{{ $order['order_number'] ?? $order['id'] }}</p>
                            <p class="text-xs font-bold text-black/40">{{ $customerName !== '' ? $customerName : 'Walk-in customer' }}</p>
                        </td>
                        <td class="px-5 py-4 font-bold">{{ count($order['items'] ?? []) }} line item(s)</td>
                        <td class="px-5 py-4 font-black">₱{{ number_format($order['total'] ?? 0, 2) }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $statusClass }}">{{ strtoupper($statusValue) }}</span>
                        </td>
                        <td class="px-5 py-4 text-sm font-bold text-black/50">
                            {{ date('M d, Y h:i A', strtotime($order['created_at'] ?? now())) }}
                        </td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ url('/admin/orders/'.$order['id']) }}"
                               class="rounded-xl border-2 border-black px-4 py-2 text-sm font-black">
                                VIEW
                            </a>
                        </td>
                    </tr>
                @endforeach
            @else
                    <tr>
                        <td colspan="6" class="p-12 text-center">
                            <p class="text-2xl font-black">No orders found.</p>
                            <p class="mt-2 font-bold text-black/40">Try another search or status.</p>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection