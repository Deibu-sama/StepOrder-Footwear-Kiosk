@extends('layouts.admin')
@section('content')
<div class="flex justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-widest text-black/40">INVENTORY / PURCHASING</p><h1 class="mt-1 text-4xl font-black">Record Supplier Purchase</h1><p class="mt-2 font-bold text-black/50">Record invoice details and received quantities. Stock and average unit costs update when saved.</p></div><a href="/admin/purchases" class="font-black underline">← Purchases</a></div>
<form method="POST" action="/admin/purchases" class="mt-6 space-y-6" id="purchase-form">
@csrf
<section class="rounded-3xl border border-black/10 bg-white p-6"><h2 class="text-2xl font-black">Supplier & invoice</h2><div class="mt-5 grid gap-4 md:grid-cols-4">
<label class="font-black">SUPPLIER NAME<input name="supplier_name" required maxlength="160" value="{{ old('supplier_name') }}" class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3"></label>
<label class="font-black">INVOICE NUMBER<input name="invoice_number" maxlength="100" value="{{ old('invoice_number') }}" placeholder="Required for eligible input VAT" class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3"></label>
<label class="font-black">PURCHASE DATE<input type="date" name="purchase_date" required value="{{ old('purchase_date', now()->toDateString()) }}" class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3"></label>
<label class="font-black">INVOICE VAT AMOUNT <span class="text-xs text-black/40">(optional)</span><input type="number" name="input_vat_amount" min="0" step="0.01" value="{{ old('input_vat_amount') }}" placeholder="Use invoice's actual VAT" class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-3"><span class="mt-1 block text-xs font-bold text-black/40">Leave blank to estimate using the configured rate.</span></label>
</div></section>
<section class="rounded-3xl border border-black/10 bg-white p-6"><div class="flex items-center justify-between gap-3"><div><h2 class="text-2xl font-black">Items received</h2><p class="mt-1 text-sm font-bold text-black/40">Add each product option on the supplier invoice.</p></div><button type="button" id="add-line" class="rounded-xl border-2 border-black px-4 py-2 font-black">+ ADD ITEM</button></div><div id="lines" class="mt-5 space-y-4"></div>
<div class="mt-5 grid gap-4 md:grid-cols-2">
<label class="flex items-start gap-3 rounded-2xl border border-black/10 bg-stone-50 p-4"><input type="checkbox" name="vat_inclusive" value="1" checked class="mt-1 h-5 w-5"><span><b>Unit costs include VAT</b><span class="mt-1 block text-sm text-black/50">Uncheck if quoted costs are before VAT.</span></span></label>
<label class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4"><input type="checkbox" name="input_vat_eligible" value="1" class="mt-1 h-5 w-5"><span><b>Mark input VAT as eligible</b><span class="mt-1 block text-sm text-emerald-800/70">Only check with a valid invoice and when the purchase qualifies. Rate: {{ number_format((float)($settings['purchase_vat_rate'] ?? 12), 2) }}%.</span></span></label>
</div>
<div class="mt-5 rounded-2xl bg-stone-50 p-5"><div class="flex justify-between"><span>Subtotal</span><strong id="subtotal">0.00</strong></div><div class="mt-2 flex justify-between"><span>Estimated VAT</span><strong id="vat">0.00</strong></div><div class="mt-3 flex justify-between border-t pt-3 text-xl"><b>Estimated invoice total</b><strong id="total">0.00</strong></div><p class="mt-3 text-xs font-bold text-black/40">Preview only. Input VAT is recorded as eligible only if you mark the purchase eligible and enter an invoice number.</p></div>
</section>
<div class="flex justify-end gap-3"><a href="/admin/purchases" class="rounded-2xl border-2 border-black px-6 py-4 font-black">CANCEL</a><button class="rounded-2xl bg-black px-7 py-4 font-black text-white">SAVE PURCHASE & RECEIVE STOCK</button></div>
</form>
<script>
const products = @json($products);
const symbol = @json($settings['currency_symbol'] ?? '₱');
const rate = {{ (float)($settings['purchase_vat_rate'] ?? 12) }};
const actualVatInput = document.querySelector('[name="input_vat_amount"]');
let lineNo = 0;
const lines = document.getElementById('lines');
function addLine() {
 const i = lineNo++;
 const row = document.createElement('div'); row.className = 'purchase-line rounded-2xl border-2 border-black/10 bg-stone-50 p-4';
 const head = document.createElement('div'); head.className = 'flex justify-between font-black'; head.textContent = 'PURCHASE ITEM '+i; 
 const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = 'REMOVE'; remove.className = 'text-red-600'; remove.onclick = () => { row.remove(); totals(); }; head.appendChild(remove); row.appendChild(head);
 const grid = document.createElement('div'); grid.className = 'mt-3 grid gap-3 md:grid-cols-2'; row.appendChild(grid);
 function field(label, cls, name, type) { const wrap = document.createElement('label'); wrap.className='text-xs font-black uppercase tracking-widest text-black/50'; wrap.append(document.createTextNode(label)); const el=document.createElement(type==='select'?'select':'input'); el.className=cls+' mt-2 w-full rounded-xl border-2 border-black bg-white px-3 py-3 text-sm font-bold'; if(type!=='select') el.type=type; el.name=name; if(type==='number'){el.min='0';el.step='0.01';} wrap.appendChild(el); grid.appendChild(wrap); return el; }
 const product=field('Product','purchase-product','purchase_items['+i+'][product_id]','select'); product.required=true;
 products.forEach(p=>{const o=new Option((p.name||'Product')+' · '+(p.sku||''),p.id); product.add(o);});
 const variant=field('Option','purchase-variant','variant-temp','select'); variant.required=true;
 const size=document.createElement('input'); size.type='hidden'; size.name='purchase_items['+i+'][size]'; row.appendChild(size);
 const color=document.createElement('input'); color.type='hidden'; color.name='purchase_items['+i+'][color]'; row.appendChild(color);
 const qty=field('Quantity received','purchase-qty','purchase_items['+i+'][quantity]','number'); qty.min='1'; qty.max='99999'; qty.step='1'; qty.value='1'; qty.required=true;
 const cost=field('Unit cost','purchase-cost','purchase_items['+i+'][unit_cost]','number'); cost.max='99999999'; cost.value='0.00'; cost.required=true;
 function sync(){const p=products.find(x=>x.id===product.value); variant.innerHTML=''; (p?.variants||[]).forEach(v=>variant.add(new Option((v.size||'Standard')+' / '+(v.color||'Default')+' · stock '+(v.stock||0),JSON.stringify({size:String(v.size||''),color:String(v.color||'')})))); if(!variant.options.length) variant.add(new Option('No variants configured','')); try{const v=JSON.parse(variant.value);size.value=v.size;color.value=v.color;}catch(e){} cost.value=Number(p?.cost_price||0).toFixed(2);totals();}
 variant.addEventListener('change',()=>{try{const v=JSON.parse(variant.value);size.value=v.size;color.value=v.color;}catch(e){}});
 product.addEventListener('change',sync); qty.addEventListener('input',totals);cost.addEventListener('input',totals);
 lines.appendChild(row); sync();
}
function totals(){let s=0;document.querySelectorAll('.purchase-line').forEach(r=>s+=Number(r.querySelector('.purchase-cost')?.value||0)*Number(r.querySelector('.purchase-qty')?.value||0));const inc=document.querySelector('[name="vat_inclusive"]').checked;const estimate=rate>0?(inc?s*rate/(100+rate):s*rate/100):0;const v=actualVatInput && actualVatInput.value.trim()!==''?Number(actualVatInput.value):estimate;const t=inc?s:s+v;const f=n=>symbol+n.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});document.getElementById('subtotal').textContent=f(s);document.getElementById('vat').textContent=f(v);document.getElementById('total').textContent=f(t);}
document.getElementById('add-line').addEventListener('click',addLine);document.querySelector('[name="vat_inclusive"]').addEventListener('change',totals);actualVatInput?.addEventListener('input',totals);addLine();
</script>
@endsection
