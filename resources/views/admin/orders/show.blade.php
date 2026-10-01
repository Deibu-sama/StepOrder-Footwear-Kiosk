@extends('layouts.admin')

@section('content')
<div class="flex items-center justify-between gap-4">
    <a href="{{ url('/admin/orders') }}" class="font-black underline">← Orders</a>
    <a href="{{ url('/admin/pos') }}" class="rounded-xl bg-black px-4 py-2 text-sm font-black text-white">OPEN POS</a>
</div>

<div class="mt-5 grid gap-6 xl:grid-cols-[1fr_340px]">
    <section class="rounded-3xl border border-black/10 bg-white p-6">
        <div class="flex flex-col justify-between gap-4 border-b border-black/10 pb-6 sm:flex-row sm:items-end">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.25em] text-black/40">ORDER DETAILS</p>
                <h1 class="mt-1 text-5xl font-black">#{{ $order['order_number'] ?? $order['id'] }}</h1>
                <p class="mt-2 text-sm font-bold text-black/40">
                    {{ date('M d, Y h:i A', strtotime($order['created_at'] ?? now())) }}
                </p>
            </div>
            <div class="text-left sm:text-right">
                <p class="text-xs font-black uppercase tracking-widest text-black/40">TOTAL</p>
                <p class="text-4xl font-black">₱{{ number_format($order['total'] ?? 0, 2) }}</p>
            </div>
        </div>

        <div class="mt-6 space-y-3">
            @foreach($order['items'] ?? [] as $item)
                <div class="flex gap-4 rounded-2xl border border-black/10 bg-stone-50 p-4">
                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl bg-white">
                        @if(!empty($item['image_url']))
                            <img src="{{ $item['image_url'] }}" alt="" class="h-full w-full object-cover">
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-black">{{ $item['name'] ?? 'Product' }}</p>
                        <p class="mt-1 text-sm font-bold text-black/50">
                            Size {{ $item['size'] ?? '—' }} · {{ $item['color'] ?? '—' }} × {{ $item['quantity'] ?? 0 }}
                        </p>
                    </div>
                    <p class="shrink-0 font-black">₱{{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 0), 2) }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <aside class="space-y-5">
        <section class="rounded-3xl border-2 border-black bg-[#fff3c9] p-6">
            <p class="text-xs font-black uppercase tracking-widest text-black/40">CURRENT STATUS</p>
            <p class="mt-2 text-3xl font-black uppercase">{{ $order['status'] ?? 'pending' }}</p>

            @if(!empty($order['paid_at']))
                <p class="mt-3 text-sm font-bold text-green-700">
                    Paid {{ date('M d, Y h:i A', strtotime($order['paid_at'])) }}
                </p>
            @endif

            @if(!empty($order['completed_at']))
                <p class="mt-1 text-sm font-bold text-green-700">
                    Released {{ date('M d, Y h:i A', strtotime($order['completed_at'])) }}
                </p>
            @endif
        </section>

        <section class="rounded-3xl border border-black/10 bg-white p-5">
            <p class="text-xs font-black uppercase tracking-widest text-black/40">CASHIER ACTIONS</p>
            <div class="mt-4 grid gap-2">
                @if(($order['status'] ?? 'pending') === 'pending')
                    <form method="POST" action="{{ url('/admin/orders/'.$order['id'].'/status') }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="paid">
                        <button class="w-full rounded-2xl bg-black px-4 py-4 font-black text-white">✓ MARK AS PAID</button>
                    </form>
                @elseif(($order['status'] ?? '') === 'paid')
                    <form method="POST" action="{{ url('/admin/orders/'.$order['id'].'/status') }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="completed">
                        <button class="w-full rounded-2xl bg-lime-400 px-4 py-4 font-black">✓ MARK AS RELEASED</button>
                    </form>
                @endif

                @if(($order['status'] ?? '') !== 'cancelled')
                    <form method="POST" action="{{ url('/admin/orders/'.$order['id'].'/status') }}"
                          onsubmit="return confirm('Cancel this order and return its stock to inventory?')">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="cancelled">
                        <button class="w-full rounded-2xl border-2 border-red-200 bg-red-50 px-4 py-4 font-black text-red-600">CANCEL ORDER</button>
                    </form>
                @endif
            </div>
        </section>

        <section class="rounded-3xl border border-black/10 bg-white p-5">
            <p class="text-xs font-black uppercase tracking-widest text-black/40">ORDER NOTES</p>
            <p class="mt-3 text-sm font-bold text-black/50">
                This is a walk-in kiosk order. Payment is collected at the cashier.
            </p>
        </section>
    </aside>
</div>
@endsection