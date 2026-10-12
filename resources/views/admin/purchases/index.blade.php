@extends('layouts.admin')

@section('content')
<div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
    <div>
        <p class="text-xs font-black uppercase tracking-[0.25em] text-black/40">INVENTORY / PURCHASING</p>
        <h1 class="mt-1 text-4xl font-black">Purchases & Receiving</h1>
        <p class="mt-2 max-w-3xl font-bold text-black/50">Record supplier invoices, receive stock, and track potentially creditable input VAT.</p>
    </div>
    <a href="/admin/purchases/create" class="rounded-2xl bg-lime-300 px-5 py-3 font-black">+ RECORD PURCHASE</a>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-3">
    <div class="rounded-3xl border border-black/10 bg-white p-5">
        <p class="text-xs font-black uppercase tracking-widest text-black/40">PURCHASE RECORDS</p>
        <p class="mt-2 text-3xl font-black">{{ number_format(count($purchases)) }}</p>
    </div>
    <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5">
        <p class="text-xs font-black uppercase tracking-widest text-emerald-700/60">RECORDED ELIGIBLE INPUT VAT</p>
        <p class="mt-2 text-3xl font-black text-emerald-700">{{ $settings['currency_symbol'] ?? '₱' }}{{ number_format($eligibleInputVat, 2) }}</p>
        <p class="mt-1 text-xs font-bold text-emerald-700/70">Management estimate; confirm invoice eligibility with your accountant.</p>
    </div>
    <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5">
        <p class="text-xs font-black uppercase tracking-widest text-amber-700/60">TARGET GROSS MARGIN</p>
        <p class="mt-2 text-3xl font-black text-amber-800">{{ number_format((float)($settings['target_gross_margin_percent'] ?? 30), 1) }}%</p>
        <p class="mt-1 text-xs font-bold text-amber-800/70">Used for recommended product prices.</p>
    </div>
</div>

<div class="mt-6 overflow-hidden rounded-3xl border border-black/10 bg-white">
    <div class="overflow-x-auto">
        <table class="min-w-[900px] w-full text-left">
            <thead class="bg-stone-50 text-xs font-black uppercase tracking-widest text-black/50">
                <tr>
                    <th class="px-5 py-4">Purchase date / invoice</th>
                    <th class="px-5 py-4">Supplier</th>
                    <th class="px-5 py-4">Items / units</th>
                    <th class="px-5 py-4">Purchase total</th>
                    <th class="px-5 py-4">Eligible input VAT</th>
                    <th class="px-5 py-4">VAT evidence</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black/10">
            @forelse($purchases as $purchase)
                <tr>
                    <td class="px-5 py-4">
                        <p class="font-black">{{ $purchase['purchase_date'] ?? '—' }}</p>
                        <p class="text-xs font-bold text-black/40">Invoice {{ $purchase['invoice_number'] ?: 'not recorded' }}</p>
                    </td>
                    <td class="px-5 py-4 font-bold">{{ $purchase['supplier_name'] ?? 'Supplier' }}</td>
                    <td class="px-5 py-4">
                        <p class="font-black">{{ count($purchase['items'] ?? []) }} line(s)</p>
                        <p class="text-xs font-bold text-black/40">{{ number_format((int)($purchase['unit_count'] ?? 0)) }} units</p>
                    </td>
                    <td class="px-5 py-4 font-black">{{ $settings['currency_symbol'] ?? '₱' }}{{ number_format((float)($purchase['total'] ?? 0), 2) }}</td>
                    <td class="px-5 py-4 font-black">{{ $settings['currency_symbol'] ?? '₱' }}{{ number_format((float)($purchase['eligible_input_vat'] ?? 0), 2) }}</td>
                    <td class="px-5 py-4">
                        @if(!empty($purchase['input_vat_eligible']) && filled($purchase['invoice_number'] ?? ''))
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-800">Marked eligible</span>
                        @else
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800">Not claimable in report</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="p-12 text-center"><p class="text-xl font-black">No purchases recorded yet.</p><p class="mt-2 font-bold text-black/40">Record a supplier purchase to receive inventory and track input VAT.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<p class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-900">Only mark input VAT as eligible when the purchase qualifies under applicable tax rules and you have the required supplier documentation. This is an operational tracker, not a tax determination.</p>
@endsection
