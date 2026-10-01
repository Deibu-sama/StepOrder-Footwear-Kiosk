@extends('layouts.admin')

@section('content')
<div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
    <div>
        <p class="text-xs font-black uppercase tracking-[0.25em] text-black/40">AUDIT TRAIL</p>
        <h1 class="mt-1 text-4xl font-black sm:text-5xl">Activity & Records</h1>
        <p class="mt-2 max-w-3xl font-bold text-black/50">A chronological record of logins, cashier changes, payments, sales, releases, and cancellations.</p>
    </div>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-3">
    <div class="rounded-3xl border border-blue-200 bg-blue-50 p-5">
        <p class="text-xs font-black uppercase tracking-widest text-blue-700/60">EVENTS</p>
        <p class="mt-2 text-4xl font-black text-blue-700">{{ count($logs) }}</p>
    </div>
    <div class="rounded-3xl border border-green-200 bg-green-50 p-5">
        <p class="text-xs font-black uppercase tracking-widest text-green-700/60">UNITS SOLD</p>
        <p class="mt-2 text-4xl font-black text-green-700">{{ $soldUnits }}</p>
    </div>
    <div class="rounded-3xl border border-lime-200 bg-lime-50 p-5">
        <p class="text-xs font-black uppercase tracking-widest text-lime-700/60">UNITS RELEASED</p>
        <p class="mt-2 text-4xl font-black text-lime-700">{{ $releasedUnits }}</p>
    </div>
</div>

<form method="GET" class="mt-6 grid gap-3 rounded-3xl border border-black/10 bg-white p-4 md:grid-cols-[1fr_220px_auto]">
    <input name="q" value="{{ $query }}" placeholder="Search order, cashier, staff, details..."
           class="rounded-2xl border-2 border-black px-4 py-3 font-bold">
    <select name="action" class="rounded-2xl border-2 border-black px-4 py-3 font-bold">
        <option value="">All events</option>
        @foreach(array_keys($actions) as $option)
            <option value="{{ $option }}" @selected($action === $option)>{{ str_replace('_', ' ', $option) }}</option>
        @endforeach
    </select>
    <button class="rounded-2xl bg-lime-300 px-5 py-3 font-black">FILTER</button>
</form>

<div class="mt-6 overflow-hidden rounded-3xl border border-black/10 bg-white">
    <div class="overflow-x-auto">
        <table class="min-w-[1100px] w-full text-left">
            <thead class="bg-stone-50 text-xs font-black uppercase tracking-widest text-black/50">
                <tr>
                    <th class="px-5 py-4">Time</th>
                    <th class="px-5 py-4">Event</th>
                    <th class="px-5 py-4">Actor</th>
                    <th class="px-5 py-4">Order</th>
                    <th class="px-5 py-4">Items</th>
                    <th class="px-5 py-4">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black/10">
                @forelse($logs as $log)
                    @php
                        $actionName = $log['action'] ?? 'UNKNOWN';
                        $badge = match($actionName) {
                            'ITEMS_SOLD', 'PAYMENT_RECEIVED' => 'bg-green-100 text-green-700',
                            'ITEMS_RELEASED' => 'bg-lime-100 text-lime-700',
                            'ORDER_CANCELLED' => 'bg-red-100 text-red-700',
                            'LOGIN', 'LOGOUT' => 'bg-blue-100 text-blue-700',
                            default => 'bg-stone-100 text-stone-700',
                        };
                        $units = (int)($log['unit_count'] ?? 0);
                    @endphp
                    <tr class="hover:bg-stone-50 align-top">
                        <td class="px-5 py-4 text-sm font-bold text-black/50">
                            {{ !empty($log['created_at']) ? date('M d, Y h:i A', strtotime($log['created_at'])) : '—' }}
                        </td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $badge }}">{{ str_replace('_', ' ', $actionName) }}</span>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-black">{{ $log['actor_email'] ?? 'System' }}</p>
                            <p class="text-xs font-bold uppercase text-black/40">{{ $log['actor_role'] ?? 'system' }}</p>
                        </td>
                        <td class="px-5 py-4 font-black">{{ $log['order_number'] ?? '—' }}</td>
                        <td class="px-5 py-4">
                            @if(!empty($log['items']))
                                <div class="space-y-1 text-sm">
                                    @foreach($log['items'] as $item)
                                        <p class="font-bold">{{ $item['name'] ?? 'Item' }} × {{ $item['quantity'] ?? 0 }} <span class="text-black/40">({{ $item['size'] ?? '—' }} / {{ $item['color'] ?? '—' }})</span></p>
                                    @endforeach
                                </div>
                            @else
                                <span class="font-bold text-black/30">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-sm font-bold text-black/60">
                            <p>{{ $log['details'] ?? '—' }}</p>
                            @if($units > 0)
                                <p class="mt-1 font-black">Units: {{ $units }}</p>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-12 text-center">
                        <p class="text-2xl font-black">No activity recorded yet.</p>
                        <p class="mt-2 font-bold text-black/40">New cashier and order events will appear here.</p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
