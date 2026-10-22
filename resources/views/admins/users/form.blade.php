@csrf
@if(isset($user)) @method('PUT') @endif

@php
    $currentRole = $role ?? ($user->role ?? 'admin');
    $label = $currentRole === 'pelanggan' ? 'Pelanggan' : 'Admin';
@endphp

<div>
    <label for="username" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Nama {{ $label }}</label>
    <input id="username" name="username" type="text" value="{{ old('username', $user->username ?? '') }}" required
           class="w-full px-4 py-3 rounded-xl border @error('username') border-red-500 @else border-gray-200 @enderror focus:border-[var(--primary)] focus:ring-1 focus:ring-[var(--primary)] outline-none transition-all bg-gray-50 focus:bg-white text-sm"
           placeholder="Masukkan nama {{ strtolower($label) }}">
    @error('username') <p class="mt-1 text-xs text-red-600 flex items-center gap-1"><iconify-icon icon="mdi:alert-circle" class="text-sm"></iconify-icon>{{ $message }}</p> @enderror
</div>

<div>
    <label for="email" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Email</label>
    <input id="email" name="email" type="email" value="{{ old('email', $user->email ?? '') }}" required
           class="w-full px-4 py-3 rounded-xl border @error('email') border-red-500 @else border-gray-200 @enderror focus:border-[var(--primary)] focus:ring-1 focus:ring-[var(--primary)] outline-none transition-all bg-gray-50 focus:bg-white text-sm"
           placeholder="email@gmail.com">
    @error('email') <p class="mt-1 text-xs text-red-600 flex items-center gap-1"><iconify-icon icon="mdi:alert-circle" class="text-sm"></iconify-icon>{{ $message }}</p> @enderror
</div>

<div>
    <label for="password" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Kata Sandi {{ isset($user) ? '(opsional)' : '' }}</label>
    <input id="password" name="password" type="password" {{ isset($user) ? '' : 'required' }}
           class="w-full px-4 py-3 rounded-xl border @error('password') border-red-500 @else border-gray-200 @enderror focus:border-[var(--primary)] focus:ring-1 focus:ring-[var(--primary)] outline-none transition-all bg-gray-50 focus:bg-white text-sm"
           placeholder="{{ isset($user) ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter' }}">
    @error('password') <p class="mt-1 text-xs text-red-600 flex items-center gap-1"><iconify-icon icon="mdi:alert-circle" class="text-sm"></iconify-icon>{{ $message }}</p> @enderror
</div>

<div>
    <label for="password_confirmation" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Konfirmasi Kata Sandi</label>
    <input id="password_confirmation" name="password_confirmation" type="password" {{ isset($user) ? '' : 'required' }}
           class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[var(--primary)] focus:ring-1 focus:ring-[var(--primary)] outline-none transition-all bg-gray-50 focus:bg-white text-sm"
           placeholder="Masukkan ulang kata sandi">
</div>

<div class="flex justify-end gap-3 pt-2">
    <a href="{{ route('admin.users.index', ['role' => $currentRole]) }}" class="px-5 py-3 rounded-xl border border-gray-200 text-gray-600 font-semibold text-sm hover:bg-gray-50 transition">Batal</a>
    <button class="px-5 py-3 rounded-xl bg-[var(--primary)] text-white font-semibold text-sm hover:bg-[var(--primary-dark)] transition shadow-lg shadow-red-200/50">
        Simpan {{ $label }}
    </button>
</div>
