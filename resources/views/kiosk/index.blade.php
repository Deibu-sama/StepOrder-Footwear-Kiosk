@extends('layouts.kiosk')

@section('content')
<main class="mx-auto max-w-6xl px-5 py-8">
    <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <a href="{{ url('/') }}" class="font-black uppercase text-black/50">← Start Screen</a>
            <p class="mt-3 font-bold uppercase tracking-widest text-black/50">FOOTWEAR ORDERING KIOSK</p>
            <h1 class="mt-2 text-5xl font-black">Choose your step.</h1>
        </div>

        <form method="GET" action="{{ url('/menu') }}" class="flex gap-2">
            <input name="q" value="{{ $search }}" placeholder="Search footwear"
                   class="min-w-0 rounded-full border-2 border-black bg-white px-5 py-3">
            <button class="rounded-full bg-black px-6 py-3 font-black text-white">Search</button>
        </form>
    </div>

    <div class="mb-8 flex gap-3 overflow-x-auto pb-2">
        <a href="{{ url('/menu') }}"
           class="shrink-0 rounded-full border-2 border-black px-5 py-3 font-black {{ !$selectedCategory ? 'bg-lime-400' : 'bg-white' }}">
            ALL
        </a>

        @foreach($categories as $category)
            <a href="{{ url('/menu?category='.$category['id']) }}"
               class="shrink-0 rounded-full border-2 border-black bg-white px-5 py-3 font-black {{ $selectedCategory === $category['id'] ? 'bg-lime-400' : '' }}">
                {{ $category['name'] }}
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-2 gap-5 md:grid-cols-3">
        @forelse($products as $product)
            <a href="{{ url('/products/'.$product['id']) }}"
               class="rounded-3xl border-2 border-black bg-[#d7e84e] p-4 transition hover:-translate-y-1">
                <div class="aspect-square overflow-hidden rounded-2xl bg-white">
                    <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}" class="h-full w-full object-cover">
                </div>

                <h2 class="mt-4 text-xl font-black uppercase">{{ $product['name'] }}</h2>
                <p class="mt-1 font-black">₱{{ number_format($product['price'], 2) }}</p>
                <p class="mt-2 text-xs font-bold uppercase opacity-60">{{ $product['category_name'] }}</p>
            </a>
        @empty
            <div class="col-span-full rounded-3xl border-2 border-black bg-white p-10 text-center">
                <p class="text-2xl font-black">No footwear found.</p>
                <p class="mt-2 font-bold text-black/50">Please ask the administrator to add products.</p>
            </div>
        @endforelse
    </div>
</main>
@endsection
