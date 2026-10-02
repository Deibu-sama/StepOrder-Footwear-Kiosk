@extends('layouts.admin')

@section('content')
<div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
    <div>
        <p class="text-xs font-black uppercase tracking-[0.25em] text-black/40">REPORTING</p>
        <h1 class="mt-1 text-4xl font-black tracking-tight sm:text-5xl">Excel Reports</h1>
        <p class="mt-2 max-w-3xl font-bold text-black/50">
            Generate a polished management workbook covering sales, orders, inventory, and the audit trail.
        </p>
    </div>

    <a href="/admin/activity"
       class="rounded-2xl border-2 border-black bg-white px-5 py-3 font-black">
        VIEW ACTIVITY
    </a>
</div>

<div class="mt-7 rounded-3xl border border-black/10 bg-white p-6">
    <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-black/40">REPORT SCOPE</p>
            <h2 class="mt-1 text-2xl font-black">Choose your reporting period</h2>
            <p class="mt-1 text-sm font-bold text-black/40">
                The workbook includes six formatted sheets and reconciled totals from Firestore.
            </p>
        </div>

        <div class="rounded-2xl bg-lime-300 px-4 py-3 text-sm font-black">
            XLSX · Excel 2007+
        </div>
    </div>

    <form method="GET" action="/admin/reports/export" class="mt-6 grid gap-4 lg:grid-cols-[1fr_1fr_220px_auto]">
        <label class="block">
            <span class="text-xs font-black uppercase tracking-widest text-black/40">From</span>
            <input type="date"
                   name="from"
                   value="{{ $filters['from'] }}"
                   class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
        </label>

        <label class="block">
            <span class="text-xs font-black uppercase tracking-widest text-black/40">To</span>
            <input type="date"
                   name="to"
                   value="{{ $filters['to'] }}"
                   class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
        </label>

        <label class="block">
            <span class="text-xs font-black uppercase tracking-widest text-black/40">Order status</span>
            <select name="status"
                    class="mt-2 w-full rounded-2xl border-2 border-black bg-white px-4 py-3 font-bold">
                <option value="" {{ $filters['status'] === '' ? 'selected' : '' }}>All statuses</option>
                <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="paid" {{ $filters['status'] === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="completed" {{ $filters['status'] === 'completed' ? 'selected' : '' }}>Completed / Released</option>
                <option value="cancelled" {{ $filters['status'] === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </label>

        <button class="self-end rounded-2xl bg-lime-300 px-6 py-3 font-black">
            DOWNLOAD EXCEL
        </button>
    </form>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-3xl border border-blue-200 bg-blue-50 p-5">
        <p class="text-xs font-black uppercase tracking-widest text-blue-700/60">ORDERS IN SCOPE</p>
        <p class="mt-2 text-4xl font-black text-blue-700">{{ number_format(count($data['orders'])) }}</p>
        <p class="mt-2 text-sm font-bold text-blue-700/60">All statuses</p>
    </div>

    <div class="rounded-3xl border border-green-200 bg-green-50 p-5">
        <p class="text-xs font-black uppercase tracking-widest text-green-700/60">NET SALES</p>
        <p class="mt-2 text-4xl font-black text-green-700">{{ $settings['currency_symbol'] ?? '₱' }}{{ number_format($data['revenue'], 2) }}</p>
        <p class="mt-2 text-sm font-bold text-green-700/60">{{ number_format(count($data['sales_orders'])) }} paid/completed orders</p>
    </div>

    <div class="rounded-3xl border border-lime-200 bg-lime-50 p-5">
        <p class="text-xs font-black uppercase tracking-widest text-lime-700/60">UNITS SOLD</p>
        <p class="mt-2 text-4xl font-black text-lime-700">{{ number_format($data['units_sold']) }}</p>
        <p class="mt-2 text-sm font-bold text-lime-700/60">{{ number_format($data['units_released']) }} released</p>
    </div>

    <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5">
        <p class="text-xs font-black uppercase tracking-widest text-amber-700/60">AVG ORDER</p>
        <p class="mt-2 text-4xl font-black text-amber-700">{{ $settings['currency_symbol'] ?? '₱' }}{{ number_format($data['avg_order'], 2) }}</p>
        <p class="mt-2 text-sm font-bold text-amber-700/60">{{ number_format(count($data['cancelled_orders'])) }} cancelled</p>
    </div>
</div>

<div class="mt-7 grid gap-6 xl:grid-cols-2">
    <section class="overflow-hidden rounded-3xl border border-black/10 bg-white">
        <div class="border-b border-black/10 px-6 py-5">
            <p class="text-xs font-black uppercase tracking-widest text-black/40">WORKBOOK CONTENT</p>
            <h2 class="mt-1 text-2xl font-black">What's inside</h2>
        </div>

        <div class="grid gap-3 p-6 sm:grid-cols-2">
            @foreach([
                ['01', 'Executive Summary', 'KPIs, order pipeline, inventory snapshot, top products'],
                ['02', 'Sales by Day', 'Daily transactions, revenue, units, releases, cancellations'],
                ['03', 'Product Sales', 'SKU-level sales, revenue, units, ranking, sales share'],
                ['04', 'Order Details', 'Complete order history with payment/release timestamps'],
                ['05', 'Inventory Snapshot', 'Current stock, low-stock and out-of-stock variants'],
                ['06', 'Activity Log', 'Audit trail of payments, sales, releases, cancellations'],
            ] as [$number, $title, $description])
                <div class="rounded-2xl border border-black/10 bg-stone-50 p-4">
                    <span class="inline-flex rounded-full bg-lime-300 px-2.5 py-1 text-[10px] font-black">{{ $number }}</span>
                    <p class="mt-3 font-black">{{ $title }}</p>
                    <p class="mt-1 text-sm font-bold text-black/50">{{ $description }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-3xl border border-black/10 bg-black p-6 text-white">
        <p class="text-xs font-black uppercase tracking-widest text-white/40">REPORT QUALITY</p>
        <h2 class="mt-1 text-2xl font-black">Built for management review</h2>

        <div class="mt-5 space-y-3">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                <p class="font-black">Reconciled operational totals</p>
                <p class="mt-1 text-sm font-bold text-white/50">Sales only include paid/completed orders; cancellations remain visible separately.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                <p class="font-black">Filterable Excel tables</p>
                <p class="mt-1 text-sm font-bold text-white/50">Freeze panes, filters, number formats, readable widths, and wrapped detail columns.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                <p class="font-black">Ready for printing or editing</p>
                <p class="mt-1 text-sm font-bold text-white/50">The workbook is designed for follow-up analysis, submission, or management presentation.</p>
            </div>
        </div>

        <div class="mt-6">
            <a href="/admin/reports/export?from={{ $filters['from'] }}&to={{ $filters['to'] }}&status={{ $filters['status'] }}"
               class="inline-flex rounded-2xl bg-lime-300 px-6 py-4 font-black text-black">
                DOWNLOAD CURRENT REPORT
            </a>
        </div>
    </section>
</div>
@endsection
