@extends('layouts.admin')

@section('content')
<div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
    <div>
        <p class="text-xs font-black uppercase tracking-[0.25em] text-black/40">ACCESS CONTROL</p>
        <h1 class="mt-1 text-4xl font-black sm:text-5xl">Cashiers</h1>
        <p class="mt-2 font-bold text-black/50">Create staff accounts, control access, and review cashier activity.</p>
    </div>
    <a href="{{ url('/admin/staff/create') }}" class="rounded-2xl bg-black px-5 py-3 font-black text-white">+ ADD CASHIER</a>
</div>

<form method="GET" class="mt-6 grid gap-3 rounded-3xl border border-black/10 bg-white p-4 md:grid-cols-[1fr_180px_auto]">
    <input name="q" value="{{ $query }}" placeholder="Search name or email..."
           class="rounded-2xl border-2 border-black px-4 py-3 font-bold">
    <select name="status" class="rounded-2xl border-2 border-black px-4 py-3 font-bold">
        <option value="">All access</option>
        <option value="active" @selected($status === 'active')>ACTIVE</option>
        <option value="disabled" @selected($status === 'disabled')>DISABLED</option>
    </select>
    <button class="rounded-2xl bg-lime-300 px-5 py-3 font-black">FILTER</button>
</form>

<div class="mt-6 grid gap-4 sm:grid-cols-3">
    <div class="rounded-3xl border border-black/10 bg-white p-5">
        <p class="text-xs font-black uppercase tracking-widest text-black/40">VISIBLE CASHIERS</p>
        <p class="mt-2 text-4xl font-black">{{ count($staff) }}</p>
    </div>
    <div class="rounded-3xl border border-green-200 bg-green-50 p-5">
        <p class="text-xs font-black uppercase tracking-widest text-green-700/60">ACTIVE</p>
        <p class="mt-2 text-4xl font-black text-green-700">{{ count(array_filter($staff, fn($row) => $row['active'] ?? false)) }}</p>
    </div>
    <div class="rounded-3xl border border-red-200 bg-red-50 p-5">
        <p class="text-xs font-black uppercase tracking-widest text-red-700/60">DISABLED</p>
        <p class="mt-2 text-4xl font-black text-red-700">{{ count(array_filter($staff, fn($row) => !($row['active'] ?? false))) }}</p>
    </div>
</div>

<div class="mt-6 overflow-hidden rounded-3xl border border-black/10 bg-white">
    <div class="overflow-x-auto">
        <table class="min-w-[900px] w-full text-left">
            <thead class="bg-stone-50 text-xs font-black uppercase tracking-widest text-black/50">
                <tr>
                    <th class="px-5 py-4">Cashier</th>
                    <th class="px-5 py-4">Access</th>
                    <th class="px-5 py-4">Created</th>
                    <th class="px-5 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black/10">
                @if(count($staff) > 0)
                @foreach($staff as $row)
                    @php($active = (bool)($row['active'] ?? false))
                    <tr class="hover:bg-stone-50">
                        <td class="px-5 py-4">
                            <p class="font-black">{{ $row['name'] ?? 'Unnamed cashier' }}</p>
                            <p class="text-sm font-bold text-black/40">{{ $row['email'] ?? '—' }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $active ? 'ENABLED' : 'DISABLED' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-sm font-bold text-black/50">
                            {{ !empty($row['created_at']) ? date('M d, Y h:i A', strtotime($row['created_at'])) : '—' }}
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <a href="{{ url('/admin/staff/'.$row['id'].'/edit') }}" class="rounded-xl border-2 border-black px-4 py-2 text-sm font-black">EDIT</a>
                                <form method="POST" action="/admin/staff/{{ $row['id'] }}/toggle">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-xl border-2 border-black bg-lime-300 px-4 py-2 text-sm font-black">
                                        {{ $active ? 'DISABLE' : 'ENABLE' }}
                                    </button>
                                </form>
                                <form method="POST" action="/admin/staff/{{ $row['id'] }}"
                                      onsubmit="return confirm('Delete this cashier account? Existing sales records will remain intact.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-xl border-2 border-red-200 bg-red-50 px-4 py-2 text-sm font-black text-red-600">DELETE</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            @else
                    <tr><td colspan="4" class="p-12 text-center">
                        <p class="text-2xl font-black">No cashier accounts found.</p>
                        <p class="mt-2 font-bold text-black/40">Create the first cashier account to begin.</p>
                    </td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
