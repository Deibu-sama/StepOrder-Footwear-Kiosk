@extends('layouts.kiosk')

@section('content')
<main class="mx-auto max-w-5xl px-5 py-8">
    <div class="relative grid grid-cols-[1fr_auto_1fr] items-center gap-3">
        <a href="{{ url('/menu') }}" class="justify-self-start rounded-2xl border-2 border-black bg-white px-5 py-3 font-black">← SHOP MORE</a>
        <div class="text-center">
            <p class="text-xs font-black uppercase tracking-widest text-black/40">STEPORDER</p>
            <h1 class="text-4xl font-black">YOUR ORDER</h1>
        </div>
        <div></div>
    </div>

    <div class="mt-6 space-y-4">
        @forelse($cart as $key => $item)
            <div class="flex flex-col gap-4 rounded-3xl border-2 border-black bg-white p-4 sm:flex-row sm:items-center">
                <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="h-28 w-28 rounded-2xl object-cover">

                <div class="min-w-0 flex-1">
                    <h2 class="font-black">{{ $item['name'] }}</h2>
                    <p class="text-sm font-bold text-black/50">Size {{ $item['size'] }} · {{ $item['color'] }}</p>

                    @if(!empty($item['sale_price']))
                        <div class="mt-1 flex items-center gap-2">
                            <p class="font-black text-red-600">₱{{ number_format($item['price'], 2) }}</p>
                            <p class="text-sm font-bold text-black/40 line-through">₱{{ number_format($item['regular_price'], 2) }}</p>
                        </div>
                        <span class="mt-1 inline-block rounded-full bg-red-100 px-2 py-1 text-[10px] font-black text-red-600">SALE</span>
                    @else
                        <p class="mt-1 font-black">₱{{ number_format($item['price'], 2) }}</p>
                    @endif
                </div>

                <form method="POST" action="{{ url('/cart/update') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="key" value="{{ $key }}">
                    <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" max="{{ $item['stock'] }}"
                           class="w-20 rounded-xl border-2 border-black px-3 py-2 font-bold">
                    <button class="rounded-xl bg-lime-400 px-4 py-2 font-black">UPDATE</button>
                </form>

                <form method="POST" action="{{ url('/cart/remove') }}">
                    @csrf
                    <input type="hidden" name="key" value="{{ $key }}">
                    <button class="font-black text-red-600">REMOVE</button>
                </form>
            </div>
        @empty
            <div class="rounded-3xl border-2 border-black bg-white p-12 text-center font-black">
                <p class="text-2xl">Your cart is empty.</p>
                <a href="{{ url('/menu') }}" class="mt-5 inline-block rounded-2xl bg-black px-6 py-4 font-black text-white">BROWSE FOOTWEAR</a>
            </div>
        @endforelse
    </div>

    @if($cart)
        <div class="mt-8 flex flex-col gap-4 rounded-3xl border-2 border-black bg-[#d7e84e] p-6 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="font-bold uppercase">TOTAL</p>
                <p class="text-4xl font-black">₱{{ number_format($total, 2) }}</p>
            </div>
            <a href="{{ url('/checkout') }}" class="rounded-2xl bg-black px-8 py-5 text-center font-black text-white">PROCEED TO CASHIER</a>
        </div>
    @endif
</main>
@endsection
