@extends('layouts.admin')

@section('title', 'Pengaturan Superadmin – Apotek Alfina Rizqy')

@section('content')
<div class="space-y-6 max-w-6xl">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between sm:items-end gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <span>Superadmin</span>
                <span>/</span>
                <span class="text-[var(--primary)]">Pengaturan</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Pengaturan Superadmin</h1>
            <p class="text-gray-500 text-sm mt-1">Kelola target operasional bulanan dan pengumuman untuk admin apotek</p>
        </div>
        <div class="bg-white px-4 py-2 rounded-xl shadow-sm border border-gray-200 flex items-center gap-2">
            <iconify-icon icon="mdi:shield-crown" class="text-[var(--primary)] text-lg"></iconify-icon>
            <span class="text-xs font-semibold text-gray-700">Khusus Superadmin</span>
        </div>
    </div>

    {{-- Grid Dua Kartu Pengaturan --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

        {{-- Form 1: Target Omzet Bulanan --}}
        <div class="bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-sm">
                        <iconify-icon icon="mdi:cash-multiple"></iconify-icon>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-800">Target Omzet Bulanan</h2>
                        <p class="text-xs text-gray-400">Periode: {{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</p>
                    </div>
                </div>
                @if($targetOmzet)
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    Aktif
                </span>
                @else
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                    Belum Diatur
                </span>
                @endif
            </div>

            <form action="{{ route('admin.settings.target.update') }}" method="POST" class="p-6 space-y-5">
                @csrf
                @method('PUT')

                {{-- Status Saat Ini --}}
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1">
                    <p class="text-xs font-semibold text-gray-500">Target Bulan Ini:</p>
                    <p class="text-xl font-extrabold text-gray-800">
                        @if($targetOmzet)
                            Rp{{ number_format($targetOmzet->target, 0, ',', '.') }}
                        @else
                            <span class="text-gray-400 text-sm font-medium">Belum ditentukan untuk bulan ini</span>
                        @endif
                    </p>
                </div>

                <div>
                    <label for="target" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                        Nominal Target (Rp) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 font-bold text-sm pointer-events-none">
                            Rp
                        </span>
                        <input type="number"
                               id="target"
                               name="target"
                               value="{{ old('target', $targetOmzet->target ?? '') }}"
                               min="1000000"
                               max="30000000"
                               step="1"
                               required
                               placeholder="Contoh: 15000000"
                               class="w-full pl-11 pr-4 py-2.5 text-sm bg-white border @error('target') border-red-400 bg-red-50/20 @else border-gray-300 @enderror rounded-xl focus:ring-2 focus:ring-[var(--primary)] focus:border-[var(--primary)] transition-all">
                    </div>

                    @error('target')
                    <p class="text-xs text-red-600 mt-2 flex items-center gap-1 font-medium">
                        <iconify-icon icon="mdi:alert-circle" class="text-sm shrink-0"></iconify-icon>
                        <span>{{ $message }}</span>
                    </p>
                    @enderror

                    <p class="text-xs text-gray-400 mt-2">
                        Minimal <strong>Rp1.000.000</strong> dan maksimal <strong>Rp30.000.000</strong> (bilangan bulat tanpa titik/koma).
                    </p>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-[var(--primary)] hover:bg-[var(--primary-dark)] text-white font-semibold text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2">
                        <iconify-icon icon="mdi:content-save-outline" class="text-lg"></iconify-icon>
                        <span>Simpan Target Omzet</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Form 2: Pengumuman Admin --}}
        <div class="bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shadow-sm">
                        <iconify-icon icon="mdi:bullhorn-outline"></iconify-icon>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-800">Pengumuman Admin</h2>
                        <p class="text-xs text-gray-400">Tampil di dashboard seluruh admin</p>
                    </div>
                </div>
                @if($pengumuman)
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                    Aktif
                </span>
                @else
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-600 border border-gray-200">
                    Kosong
                </span>
                @endif
            </div>

            <form action="{{ route('admin.settings.announcement.update') }}" method="POST" class="p-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="konten" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                        Teks Pengumuman <span class="text-red-500">*</span>
                    </label>
                    <textarea id="konten"
                              name="konten"
                              rows="5"
                              minlength="20"
                              maxlength="300"
                              required
                              placeholder="Tuliskan pengumuman penting untuk seluruh admin apotek (20 - 300 karakter)..."
                              class="w-full p-3.5 text-sm bg-white border @error('konten') border-red-400 bg-red-50/20 @else border-gray-300 @enderror rounded-xl focus:ring-2 focus:ring-[var(--primary)] focus:border-[var(--primary)] transition-all">{{ old('konten', $pengumuman->konten ?? '') }}</textarea>

                    <div class="flex justify-between items-center mt-1.5">
                        <span class="text-xs text-gray-400">20–300 karakter, tanpa tag HTML.</span>
                        <span id="charCount" class="text-xs font-bold text-gray-500">0/300</span>
                    </div>

                    @error('konten')
                    <p class="text-xs text-red-600 mt-2 flex items-center gap-1 font-medium">
                        <iconify-icon icon="mdi:alert-circle" class="text-sm shrink-0"></iconify-icon>
                        <span>{{ $message }}</span>
                    </p>
                    @enderror
                </div>

                @if($pengumuman)
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-gray-500 flex items-center justify-between">
                    <span>Terakhir diperbarui:</span>
                    <span class="font-semibold text-gray-700">{{ $pengumuman->updated_at->translatedFormat('d F Y, H:i') }} WIB</span>
                </div>
                @endif

                <div class="pt-2">
                    <button type="submit"
                            class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-[var(--primary)] hover:bg-[var(--primary-dark)] text-white font-semibold text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2">
                        <iconify-icon icon="mdi:content-save-outline" class="text-lg"></iconify-icon>
                        <span>Simpan Pengumuman</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const textarea = document.getElementById('konten');
        const counter = document.getElementById('charCount');

        if (textarea && counter) {
            const updateCount = () => {
                const len = textarea.value.length;
                counter.textContent = `${len}/300`;
                if (len < 20 || len > 300) {
                    counter.className = 'text-xs font-bold text-amber-600';
                } else {
                    counter.className = 'text-xs font-bold text-emerald-600';
                }
            };

            textarea.addEventListener('input', updateCount);
            updateCount();
        }
    });
</script>
@endsection
