@extends('layouts.admin')

@section('title', 'Edit Produk – ' . $obat->nama)

@section('content')
@php
    // Ambil semua deskripsi sebagai key => nilai untuk kemudahan akses
    $deskMap = $obat->deskripsi->pluck('nilai', 'label');
@endphp
<div class="space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('obat.index') }}" class="w-10 h-10 flex items-center justify-center bg-white rounded-xl shadow-sm border border-gray-200 text-gray-600 hover:text-[var(--primary)] hover:border-[var(--primary)] transition-all">
            <iconify-icon icon="mdi:arrow-left" class="text-xl"></iconify-icon>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-800">Edit Produk</h1>
            <p class="text-gray-500 text-sm mt-1">Perbarui informasi untuk <span class="text-[var(--primary)] font-semibold">{{ $obat->nama }}</span></p>
        </div>
    </div>

    <form action="{{ route('obat.update', $obat->id) }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf
        @method('PUT')

        {{-- Kolom Kiri --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Card: Informasi Dasar --}}
            <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 space-y-5">
                <h2 class="text-sm font-bold text-gray-700 flex items-center gap-2 pb-3 border-b border-gray-100">
                    <iconify-icon icon="mdi:information-outline" class="text-[var(--primary)]"></iconify-icon>
                    Informasi Dasar
                </h2>

                <div class="space-y-2">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Nama Produk *</label>
                    <input type="text" name="nama" value="{{ old('nama', $obat->nama) }}" required
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm font-semibold text-gray-700">
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Kategori *</label>
                    <select name="kategori" required
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm font-semibold text-gray-700">
                        <option value="">Pilih Kategori</option>
                        @foreach(['Alat Kesehatan', 'Obat Bebas', 'Obat Keras', 'Perawatan Tubuh', 'Vitamin dan Suplemen', 'Makanan dan Minuman'] as $kat)
                            <option value="{{ $kat }}" {{ old('kategori', $obat->kategori) == $kat ? 'selected' : '' }}>{{ $kat }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Harga Satuan (Rp) *</label>
                        <input type="number" name="harga" value="{{ old('harga', $obat->harga) }}"
                               required min="1" max="9999999" step="1"
                               class="w-full bg-gray-50 border {{ $errors->has('harga') ? 'border-red-500' : 'border-gray-200 focus:border-[var(--primary)]' }} rounded-xl px-4 py-3 focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm font-semibold text-gray-700">
                        @error('harga')<p class="text-xs text-red-600 font-medium flex items-center gap-1"><iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Stok Tersedia *</label>
                        <input type="number" name="stok" value="{{ old('stok', $obat->stok) }}"
                               required min="0" max="5000" step="1"
                               class="w-full bg-gray-50 border {{ $errors->has('stok') ? 'border-red-500' : 'border-gray-200 focus:border-[var(--primary)]' }} rounded-xl px-4 py-3 focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm font-semibold text-gray-700">
                        @error('stok')<p class="text-xs text-red-600 font-medium flex items-center gap-1"><iconify-icon icon="mdi:alert-circle-outline"></iconify-icon>{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Diskon (%)</label>
                    <select name="diskon_persen"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm font-semibold text-gray-700">
                        @foreach([0,5,10,15,20,25,30,40,50] as $d)
                            <option value="{{ $d }}" {{ old('diskon_persen', $obat->diskon_persen) == $d ? 'selected' : '' }}>{{ $d }}%{{ $d == 0 ? ' (Tanpa Diskon)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Card: Deskripsi Produk --}}
            <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 space-y-5">
                <h2 class="text-sm font-bold text-gray-700 flex items-center gap-2 pb-3 border-b border-gray-100">
                    <iconify-icon icon="mdi:text-box-outline" class="text-[var(--primary)]"></iconify-icon>
                    Deskripsi Produk
                </h2>

                {{-- Product Description --}}
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Product Description *</label>
                    <textarea name="deskripsi" id="deskripsi" rows="4" required minlength="10" maxlength="1000"
                              class="w-full bg-gray-50 border @error('deskripsi') border-red-500 @else border-gray-200 @enderror rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700 resize-none"
                              placeholder="Deskripsi umum produk...">{{ old('deskripsi', $deskMap->get('Product Description', '')) }}</textarea>
                    <div class="flex justify-between">
                        @error('deskripsi')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        <span id="char-count" class="text-[11px] font-semibold text-gray-400 ml-auto">0/1000</span>
                    </div>
                </div>

                {{-- Indikasi Umum --}}
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Indikasi Umum</label>
                    <textarea name="indikasi_umum" rows="2"
                              class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700 resize-none"
                              placeholder="Contoh: Untuk mengurangi gejala demam dan nyeri ringan...">{{ old('indikasi_umum', $deskMap->get('Indikasi Umum', '')) }}</textarea>
                </div>

                {{-- Komposisi --}}
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Komposisi</label>
                    <textarea name="komposisi" rows="2"
                              class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700 resize-none"
                              placeholder="Contoh: Paracetamol 500mg, Cafein 50mg...">{{ old('komposisi', $deskMap->get('Komposisi', '')) }}</textarea>
                </div>

                {{-- Dosis & Aturan Pakai --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Dosis</label>
                        <textarea name="dosis" rows="2"
                                  class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700 resize-none"
                                  placeholder="Contoh: 1 tablet 3x sehari...">{{ old('dosis', $deskMap->get('Dosis', '')) }}</textarea>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Aturan Pakai</label>
                        <textarea name="aturan_pakai" rows="2"
                                  class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700 resize-none"
                                  placeholder="Contoh: Diminum sesudah makan...">{{ old('aturan_pakai', $deskMap->get('Aturan Pakai', '')) }}</textarea>
                    </div>
                </div>

                {{-- Kontra Indikasi & Perhatian --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Kontra Indikasi</label>
                        <textarea name="kontra_indikasi" rows="2"
                                  class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700 resize-none"
                                  placeholder="Contoh: Tidak untuk penderita maag akut...">{{ old('kontra_indikasi', $deskMap->get('Kontra Indikasi', '')) }}</textarea>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Perhatian</label>
                        <textarea name="perhatian" rows="2"
                                  class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700 resize-none"
                                  placeholder="Contoh: Hati-hati pada ibu hamil...">{{ old('perhatian', $deskMap->get('Perhatian', '')) }}</textarea>
                    </div>
                </div>

                {{-- Efek Samping --}}
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Efek Samping</label>
                    <textarea name="efek_samping" rows="2"
                              class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700 resize-none"
                              placeholder="Contoh: Dapat menyebabkan mual, pusing...">{{ old('efek_samping', $deskMap->get('Efek Samping', '')) }}</textarea>
                </div>

                {{-- Kemasan & Manufaktur --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Kemasan</label>
                        <input type="text" name="kemasan" value="{{ old('kemasan', $deskMap->get('Kemasan', '')) }}"
                               class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700"
                               placeholder="Contoh: Box @ 10 Kapsul">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Manufaktur</label>
                        <input type="text" name="manufaktur" value="{{ old('manufaktur', $deskMap->get('Manufaktur', '')) }}"
                               class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700"
                               placeholder="Contoh: PT. Kimia Farma">
                    </div>
                </div>

                {{-- No. Registrasi --}}
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">No. Registrasi (BPOM)</label>
                    <input type="text" name="no_registrasi" value="{{ old('no_registrasi', $deskMap->get('No. Registrasi', '')) }}"
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700"
                           placeholder="Contoh: BPOM RI NA18170900021">
                </div>
            </div>

            {{-- Tombol Aksi --}}
            <div class="flex gap-3">
                <a href="{{ route('obat.index') }}"
                   class="flex-1 text-center px-6 py-3 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition">
                    Batal
                </a>
                <button type="submit"
                        class="flex-1 bg-[var(--primary)] text-white px-6 py-3 rounded-xl text-sm font-semibold hover:bg-[var(--primary-dark)] transition shadow-md">
                    Perbarui Produk
                </button>
            </div>
        </div>

        {{-- Kolom Kanan: Foto + Info --}}
        <div class="lg:col-span-1 space-y-6">

            {{-- Foto Produk --}}
            <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 space-y-4 sticky top-24">
                <h2 class="text-sm font-bold text-gray-700 flex items-center gap-2 pb-3 border-b border-gray-100">
                    <iconify-icon icon="mdi:image-outline" class="text-[var(--primary)]"></iconify-icon>
                    Foto Produk
                </h2>

                <div class="relative group">
                    <div id="preview-container"
                         class="w-full aspect-square bg-gray-50 rounded-xl border-2 border-dashed border-gray-200 flex items-center justify-center overflow-hidden cursor-pointer hover:border-[var(--primary)] transition">
                        <img id="image-preview"
                             src="{{ $obat->path_gambar ? asset('storage/' . $obat->path_gambar) : '' }}"
                             class="{{ $obat->path_gambar ? '' : 'hidden' }} w-full h-full object-contain p-2">
                        <div id="placeholder-content" class="{{ $obat->path_gambar ? 'hidden' : '' }} text-center">
                            <iconify-icon icon="mdi:camera-plus-outline" class="text-5xl text-gray-300 mb-2"></iconify-icon>
                            <p class="text-xs text-gray-400 font-medium">Klik untuk upload</p>
                        </div>
                    </div>
                    <input type="file" name="gambar" id="gambar-input"
                           class="absolute inset-0 opacity-0 cursor-pointer"
                           accept="image/*">
                </div>

                <div class="space-y-1">
                    <p class="text-xs text-gray-500 font-medium flex items-center gap-2">
                        <iconify-icon icon="mdi:information-outline" class="text-sm"></iconify-icon>
                        Klik untuk mengganti foto
                    </p>
                    <p class="text-xs text-gray-500 font-medium flex items-center gap-2">
                        <iconify-icon icon="mdi:file-outline" class="text-sm"></iconify-icon>
                        Format: JPG, PNG. Maks: 2MB
                    </p>
                </div>

                <button type="button" id="remove-image"
                        class="hidden w-full px-4 py-2 rounded-lg bg-red-50 text-red-600 text-xs font-semibold hover:bg-red-100 transition">
                    <iconify-icon icon="mdi:trash-can-outline" class="text-sm"></iconify-icon>
                    Batalkan Ganti Foto
                </button>

                {{-- Info Produk --}}
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <p class="text-xs font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <iconify-icon icon="mdi:database-outline"></iconify-icon>
                        Data Saat Ini
                    </p>
                    <div class="space-y-1.5 text-xs text-gray-600">
                        <div class="flex justify-between">
                            <span>ID Produk:</span>
                            <span class="font-semibold">#{{ $obat->id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Stok Saat Ini:</span>
                            <span class="font-semibold {{ $obat->stok < 10 ? 'text-red-600' : 'text-green-600' }}">{{ $obat->stok }} Unit</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Terakhir Update:</span>
                            <span class="font-semibold">{{ $obat->updated_at->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    const input = document.getElementById('gambar-input');
    const preview = document.getElementById('image-preview');
    const placeholder = document.getElementById('placeholder-content');
    const removeBtn = document.getElementById('remove-image');
    const originalSrc = preview.src;

    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran file terlalu besar! Maksimal 2MB');
                input.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
                removeBtn.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    });

    removeBtn.addEventListener('click', function() {
        input.value = '';
        if (originalSrc && originalSrc !== window.location.href) {
            preview.src = originalSrc;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        } else {
            preview.src = '';
            preview.classList.add('hidden');
            placeholder.classList.remove('hidden');
        }
        removeBtn.classList.add('hidden');
    });

    const deskripsi = document.getElementById('deskripsi');
    const charCount = document.getElementById('char-count');
    if (deskripsi && charCount) {
        const updateCount = () => { charCount.innerText = deskripsi.value.length + '/1000'; };
        deskripsi.addEventListener('input', updateCount);
        updateCount();
    }
</script>
@endsection
