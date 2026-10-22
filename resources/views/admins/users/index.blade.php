@extends('layouts.admin')

@section('title', 'Kelola Akun – Apotek Alfina Rizqy')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Kelola Akun</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ $roleFilter === 'pelanggan'
                    ? 'Kelola semua akun pelanggan yang terdaftar di sistem.'
                    : 'Akun admin hanya dapat dibuat dan dikelola oleh superadmin.' }}
            </p>
        </div>
        <a href="{{ route('admin.users.create', ['role' => $roleFilter]) }}"
           class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-[var(--primary)] text-white text-sm font-semibold rounded-xl hover:bg-[var(--primary-dark)] transition shadow-lg shadow-red-200/50">
            <iconify-icon icon="mdi:account-plus" class="text-xl"></iconify-icon>
            Tambah {{ $roleFilter === 'pelanggan' ? 'User' : 'Admin' }}
        </a>
    </div>

    {{-- Tab Filter --}}
    <div class="flex gap-2">
        <a href="{{ route('admin.users.index', ['role' => 'admin']) }}"
           class="px-5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ $roleFilter === 'admin' ? 'bg-[var(--primary)] text-white shadow-md' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
            <iconify-icon icon="mdi:shield-account" class="mr-1 align-middle"></iconify-icon>
            Admin
        </a>
        <a href="{{ route('admin.users.index', ['role' => 'pelanggan']) }}"
           class="px-5 py-2.5 rounded-xl text-sm font-semibold transition-all {{ $roleFilter === 'pelanggan' ? 'bg-[var(--primary)] text-white shadow-md' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
            <iconify-icon icon="mdi:account-group" class="mr-1 align-middle"></iconify-icon>
            User / Pelanggan
        </a>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="text-left px-6 py-4">{{ $roleFilter === 'pelanggan' ? 'Pelanggan' : 'Admin' }}</th>
                        <th class="text-left px-6 py-4">Email</th>
                        <th class="text-left px-6 py-4">Dibuat</th>
                        <th class="text-right px-6 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $account)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white text-sm font-bold {{ $roleFilter === 'pelanggan' ? 'bg-blue-500' : 'bg-amber-500' }}">
                                    {{ strtoupper(substr($account->username, 0, 1)) }}
                                </div>
                                <span class="font-semibold text-gray-800">{{ $account->username }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $account->email }}</td>
                        <td class="px-6 py-4 text-gray-500">{{ $account->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.users.edit', $account) }}" class="p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition" title="Ubah">
                                    <iconify-icon icon="mdi:pencil" class="text-lg"></iconify-icon>
                                </a>
                                <form action="{{ route('admin.users.destroy', $account) }}" method="POST" onsubmit="return confirm('Hapus akun ini? Tindakan ini tidak bisa dibatalkan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition" title="Hapus">
                                        <iconify-icon icon="mdi:delete" class="text-lg"></iconify-icon>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center">
                                    <iconify-icon icon="mdi:account-off-outline" class="text-3xl text-gray-400"></iconify-icon>
                                </div>
                                <p class="text-gray-500 font-medium">Belum ada akun {{ $roleFilter === 'pelanggan' ? 'pelanggan' : 'admin' }}.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">{{ $users->appends(['role' => $roleFilter])->links() }}</div>
        @endif
    </div>
</div>
@endsection
