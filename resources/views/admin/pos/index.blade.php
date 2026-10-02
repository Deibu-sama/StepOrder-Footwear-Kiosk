@extends('layouts.admin')

@section('content')
<div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
    <div>
        <p class="text-xs font-black uppercase tracking-[0.25em] text-black/40">CASHIER</p>
        <h1 class="mt-1 text-4xl font-black sm:text-5xl">POS</h1>
        <p class="mt-2 font-bold text-black/50">Take the order number, collect payment, and release the footwear.</p>
    </div>

    <a href="{{ url('/admin/orders') }}" class="rounded-2xl border-2 border-black bg-white px-5 py-3 font-black">ALL ORDERS</a>
</div>

@if(count($stalePending) > 0)
<div class="mt-6 rounded-3xl border-2 border-amber-300 bg-amber-50 p-5">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-amber-800/60">PAYMENT ATTENTION</p>
            <p class="mt-1 text-xl font-black text-amber-950">
                {{ count($stalePending) }} pending order(s) have been waiting longer than {{ app(AppServicesSettingsService::class)->all()['pending_order_warning_hours'] ?? 24 }} hours.
            </p>
            <p class="mt-1 text-sm font-bold text-amber-900/60">
                They will auto-cancel after {{ app(AppServicesSettingsService::class)->all()['pending_order_expiry_days'] ?? 7 }} days if still unpaid.
            </p>
        </div>
        <a href="{{ url('/admin/orders?status=pending&stale=1') }}"
           class="rounded-2xl bg-amber-300 px-5 py-3 font-black text-amber-950">
            REVIEW
        </a>
    </div>
</div>
@endif

<div class="mt-7 grid gap-5 xl:grid-cols-3">
    @php
        $columns = [
            ['title' => 'AWAITING PAYMENT', 'subtitle' => 'Customer has an order number.', 'items' => $pending, 'accent' => 'amber'],
            ['title' => 'PAID', 'subtitle' => 'Ready to be released.', 'items' => $paid, 'accent' => 'blue'],
            ['title' => 'RELEASED', 'subtitle' => 'Completed transactions.', 'items' => $completed, 'accent' => 'green'],
        ];
    @endphp

    @foreach($columns as $column)
        <section class="min-h-[420px] rounded-3xl border border-black/10 bg-white p-4">
            <div class="flex items-start justify-between gap-3 border-b border-black/10 pb-4">
                <div>
                    <h2 class="font-black">{{ $column['title'] }}</h2>
                    <p class="mt-1 text-xs font-bold text-black/40">{{ $column['subtitle'] }}</p>
                </div>
                <span class="grid h-9 min-w-9 place-items-center rounded-full bg-stone-100 px-2 font-black">{{ count($column['items']) }}</span>
            </div>

            <div class="step-scroll mt-4 max-h-[66vh] space-y-3 overflow-y-auto pr-1">
                @if(count($column['items']) > 0)
                    @foreach($column['items'] as $order)
                    <article class="rounded-2xl border-2 border-black/10 bg-stone-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-2xl font-black">#{{ $order['order_number'] }}</p>
                                <p class="mt-1 text-xs font-bold text-black/40">{{ count($order['items'] ?? []) }} line item(s)</p>
                                @if(!empty($order['_pending_stale']))
                                    <span class="mt-2 inline-flex rounded-full bg-amber-200 px-2.5 py-1 text-[10px] font-black text-amber-900">
                                        PAYMENT PENDING · {{ $order['_pending_age_label'] }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-lg font-black">₱{{ number_format($order['total'] ?? 0, 2) }}</p>
                        </div>

                        <div class="mt-3 space-y-1 text-sm font-bold text-black/60">
                            @foreach(array_slice($order['items'] ?? [], 0, 3) as $item)
                                <p>{{ $item['name'] }} · {{ $item['size'] }} · {{ $item['color'] }} ×{{ $item['quantity'] }}</p>
                            @endforeach
                            @if(count($order['items'] ?? []) > 3)
                                <p class="text-black/30">+ {{ count($order['items']) - 3 }} more</p>
                            @endif
                        </div>

                        <div class="mt-4 flex gap-2">
                            <a href="{{ url('/admin/orders/'.$order['id']) }}"
                               class="flex-1 rounded-xl border-2 border-black bg-white px-3 py-3 text-center text-sm font-black">
                                VIEW
                            </a>

                            @if(($order['status'] ?? 'pending') === 'pending')
                                <form method="POST" action="{{ url('/admin/orders/'.$order['id'].'/status') }}" class="flex-1">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="paid">
                                    <button class="w-full rounded-xl bg-black px-3 py-3 text-sm font-black text-white">
                                        MARK PAID
                                    </button>
                                </form>
                            @elseif(($order['status'] ?? '') === 'paid')
                                <form method="POST" action="{{ url('/admin/orders/'.$order['id'].'/status') }}" class="flex-1">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="completed">
                                    <button class="w-full rounded-xl bg-lime-400 px-3 py-3 text-sm font-black">
                                        RELEASE
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                    @endforeach
                @else
                    <div class="rounded-2xl border-2 border-dashed border-black/10 p-8 text-center">
                        <p class="font-black text-black/40">Nothing here.</p>
                    </div>
                @endif
            </div>
        </section>
    @endforeach
</div>

<div class="mt-6 rounded-3xl border border-black/10 bg-[#fff3c9] p-5">
    <p class="font-black">CASHIER WORKFLOW</p>
    <div class="mt-3 grid gap-3 md:grid-cols-3">
        <div class="rounded-2xl bg-white p-4"><span class="font-black">1.</span> Customer generates order at kiosk.</div>
        <div class="rounded-2xl bg-white p-4"><span class="font-black">2.</span> Cashier collects payment and taps <b>MARK PAID</b>.</div>
        <div class="rounded-2xl bg-white p-4"><span class="font-black">3.</span> Footwear is handed over and cashier taps <b>RELEASE</b>.</div>
    </div>
</div>
@endsection