@extends('layouts.admin')

@section('title', ($user->role === 'pelanggan' ? 'Ubah User' : 'Ubah Admin') . ' – Apotek Alfina Rizqy')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">
            {{ $user->role === 'pelanggan' ? 'Ubah User' : 'Ubah Admin' }}
        </h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $user->role === 'pelanggan'
                ? 'Perbarui identitas atau kata sandi akun pelanggan.'
                : 'Perbarui identitas atau kata sandi akun admin.' }}
        </p>
    </div>
    <form action="{{ route('admin.users.update', $user) }}" method="POST" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">
        @include('admins.users.form', ['user' => $user, 'role' => $user->role])
    </form>
</div>
@endsection
