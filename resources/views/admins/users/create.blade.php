@extends('layouts.admin')

@section('title', ($role === 'pelanggan' ? 'Tambah User' : 'Tambah Admin') . ' – Apotek Alfina Rizqy')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">
            {{ $role === 'pelanggan' ? 'Tambah User' : 'Tambah Admin' }}
        </h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $role === 'pelanggan'
                ? 'Buat akun baru untuk pelanggan apotek.'
                : 'Buat akun baru dengan akses pengelolaan apotek.' }}
        </p>
    </div>
    <form action="{{ route('admin.users.store') }}" method="POST" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">
        <input type="hidden" name="role" value="{{ $role }}">
        @include('admins.users.form', ['role' => $role])
    </form>
</div>
@endsection
