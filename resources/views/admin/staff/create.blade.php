@extends('layouts.admin')

@section('content')
<div class="flex items-center justify-between gap-4">
    <a href="{{ url('/admin/staff') }}" class="font-black underline">← Cashiers</a>
</div>

<div class="mx-auto mt-6 max-w-2xl rounded-3xl border border-black/10 bg-white p-6 sm:p-8">
    <p class="text-xs font-black uppercase tracking-[0.25em] text-black/40">{{ $mode === 'edit' ? 'UPDATE STAFF' : 'NEW STAFF' }}</p>
    <h1 class="mt-1 text-4xl font-black">{{ $mode === 'edit' ? 'Edit Cashier' : 'Add Cashier' }}</h1>
    <p class="mt-2 font-bold text-black/50">Cashiers can use the POS and process orders, but cannot manage system settings or catalog data.</p>

    <form method="POST" action="{{ $mode === 'edit' ? url('/admin/staff/'.$staff['id']) : url('/admin/staff') }}" class="mt-7 space-y-5">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <label class="block">
            <span class="text-xs font-black uppercase tracking-widest text-black/40">Full name</span>
            <input name="name" value="{{ old('name', $staff['name'] ?? '') }}" required
                   class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-4 font-bold">
        </label>

        <label class="block">
            <span class="text-xs font-black uppercase tracking-widest text-black/40">Email</span>
            <input type="email" name="email" value="{{ old('email', $staff['email'] ?? '') }}" required
                   class="mt-2 w-full rounded-2xl border-2 border-black px-4 py-4 font-bold">
        </label>

        <label class="block">
            <span class="text-xs font-black uppercase tracking-widest text-black/40">
                Password {{ $mode === 'edit' ? '(leave blank to keep current password)' : '' }}
            </span>
            <div class="mt-2 flex overflow-hidden rounded-2xl border-2 border-black">
                <input id="cashier-password" type="password" name="password" {{ $mode === 'create' ? 'required' : '' }}
                       class="min-w-0 flex-1 border-0 px-4 py-4 font-bold outline-none">
                <button type="button" id="toggle-cashier-password" class="border-l-2 border-black bg-stone-50 px-4 font-black">SHOW</button>
            </div>
            <span class="mt-1 block text-xs font-bold text-black/40">Minimum 8 characters.</span>
        </label>

        @if($mode === 'edit')
            <div class="flex items-center justify-between rounded-2xl border border-black/10 bg-stone-50 p-4">
                <div>
                    <p class="font-black">Account enabled</p>
                    <p class="text-sm font-bold text-black/40">Disabled cashiers cannot sign in.</p>
                </div>
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" class="h-6 w-6 accent-lime-600" @checked(old('active', $staff['active'] ?? false))>
            </div>
        @endif

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ url('/admin/staff') }}" class="rounded-2xl border-2 border-black px-6 py-4 text-center font-black">CANCEL</a>
            <button class="rounded-2xl bg-black px-7 py-4 font-black text-white">{{ $mode === 'edit' ? 'SAVE CHANGES' : 'CREATE CASHIER' }}</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const pwd = document.getElementById('cashier-password');
    const toggle = document.getElementById('toggle-cashier-password');
    toggle?.addEventListener('click', () => {
        const visible = pwd.type === 'text';
        pwd.type = visible ? 'password' : 'text';
        toggle.textContent = visible ? 'SHOW' : 'HIDE';
    });
</script>
@endpush
