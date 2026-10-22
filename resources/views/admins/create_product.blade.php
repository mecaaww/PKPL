@extends('layouts.admin')

@section('title', 'Tambah Produk – Apotek Alfina Rizqy')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('obat.index') }}" class="w-10 h-10 flex items-center justify-center bg-white rounded-xl shadow-sm border border-gray-200 text-gray-600 hover:text-[var(--primary)] hover:border-[var(--primary)] transition-all">
            <iconify-icon icon="mdi:arrow-left" class="text-xl"></iconify-icon>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-800">Tambah Produk Baru</h1>
            <p class="text-gray-500 text-sm mt-1">Lengkapi detail informasi produk di bawah ini</p>
        </div>
    </div>

    <form action="{{ route('obat.store') }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf

        {{-- Kolom Kiri: Info Utama + Deskripsi Lengkap --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Card: Informasi Dasar --}}
            <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 space-y-5">
                <h2 class="text-sm font-bold text-gray-700 flex items-center gap-2 pb-3 border-b border-gray-100">
                    <iconify-icon icon="mdi:information-outline" class="text-[var(--primary)]"></iconify-icon>
                    Informasi Dasar
                </h2>

                <div class="space-y-2">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Nama Produk *</label>
                    <input type="text" name="nama" value="{{ old('nama') }}" required
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm font-semibold text-gray-700"
                           placeholder="Contoh: Amoxicillin 500mg">
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Kategori *</label>
                    <select name="kategori" required
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm font-semibold text-gray-700">
                        <option value="">Pilih Kategori</option>
                        @foreach(['Alat Kesehatan', 'Obat Bebas', 'Obat Keras', 'Perawatan Tubuh', 'Vitamin dan Suplemen', 'Makanan dan Minuman'] as $kat)
                            <option value="{{ $kat }}" {{ old('kategori') == $kat ? 'selected' : '' }}>{{ $kat }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Harga Satuan (Rp) *</label>
                        <input type="number" name="harga" value="{{ old('harga') }}"
                               required min="1" max="9999999" step="1"
                               class="w-full bg-gray-50 border {{ $errors->has('harga') ? 'border-red-500' : 'border-gray-200 focus:border-[var(--primary)]' }} rounded-xl px-4 py-3 focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm font-semibold text-gray-700"
                               placeholder="Contoh: 25000">
                        @error('harga')
                            <p class="text-xs text-red-600 font-medium flex items-center gap-1">
                                <iconify-icon icon="mdi:alert-circle-outline" class="text-sm"></iconify-icon>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Stok Awal *</label>
                        <input type="number" name="stok" value="{{ old('stok') }}"
                               required min="0" max="5000" step="1"
                               class="w-full bg-gray-50 border {{ $errors->has('stok') ? 'border-red-500' : 'border-gray-200 focus:border-[var(--primary)]' }} rounded-xl px-4 py-3 focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm font-semibold text-gray-700"
                               placeholder="0">
                        @error('stok')
                            <p class="text-xs text-red-600 font-medium flex items-center gap-1">
                                <iconify-icon icon="mdi:alert-circle-outline" class="text-sm"></iconify-icon>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Diskon (%)</label>
                    <select name="diskon_persen"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm font-semibold text-gray-700">
                        @foreach([0,5,10,15,20,25,30,40,50] as $d)
                            <option value="{{ $d }}" {{ old('diskon_persen', 0) == $d ? 'selected' : '' }}>{{ $d }}%{{ $d == 0 ? ' (Tanpa Diskon)' : '' }}</option>
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
                              placeholder="Deskripsi umum produk...">{{ old('deskripsi') }}</textarea>
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
                              placeholder="Contoh: Untuk mengurangi gejala demam dan nyeri ringan...">{{ old('indikasi_umum') }}</textarea>
                </div>

                {{-- Komposisi --}}
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Komposisi</label>
                    <textarea name="komposisi" rows="2"
                              class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700 resize-none"
                              placeholder="Contoh: Paracetamol 500mg, Cafein 50mg...">{{ old('komposisi') }}</textarea>
                </div>

                {{-- Dosis & Aturan Pakai side by side --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Dosis</label>
                        <textarea name="dosis" rows="2"
                                  class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700 resize-none"
                                  placeholder="Contoh: 1 tablet 3x sehari...">{{ old('dosis') }}</textarea>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Aturan Pakai</label>
                        <textarea name="aturan_pakai" rows="2"
                                  class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700 resize-none"
                                  placeholder="Contoh: Diminum sesudah makan...">{{ old('aturan_pakai') }}</textarea>
                    </div>
                </div>

                {{-- Kontra Indikasi & Perhatian --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Kontra Indikasi</label>
                        <textarea name="kontra_indikasi" rows="2"
                                  class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700 resize-none"
                                  placeholder="Contoh: Tidak untuk penderita maag akut...">{{ old('kontra_indikasi') }}</textarea>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Perhatian</label>
                        <textarea name="perhatian" rows="2"
                                  class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700 resize-none"
                                  placeholder="Contoh: Hati-hati pada ibu hamil...">{{ old('perhatian') }}</textarea>
                    </div>
                </div>

                {{-- Efek Samping --}}
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Efek Samping</label>
                    <textarea name="efek_samping" rows="2"
                              class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700 resize-none"
                              placeholder="Contoh: Dapat menyebabkan mual, pusing...">{{ old('efek_samping') }}</textarea>
                </div>

                {{-- Kemasan & Manufaktur --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Kemasan</label>
                        <input type="text" name="kemasan" value="{{ old('kemasan') }}"
                               class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700"
                               placeholder="Contoh: Box @ 10 Kapsul">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Manufaktur</label>
                        <input type="text" name="manufaktur" value="{{ old('manufaktur') }}"
                               class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:border-[var(--primary)] focus:ring-2 focus:ring-[var(--primary)]/20 outline-none text-sm text-gray-700"
                               placeholder="Contoh: PT. Kimia Farma">
                    </div>
                </div>

                {{-- No. Registrasi --}}
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">No. Registrasi (BPOM)</label>
                    <input type="text" name="no_registrasi" value="{{ old('no_registrasi') }}"
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
                    Simpan Produk
                </button>
            </div>
        </div>

        {{-- Kolom Kanan: Foto Produk --}}
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 space-y-4 sticky top-24">
                <h2 class="text-sm font-bold text-gray-700 flex items-center gap-2 pb-3 border-b border-gray-100">
                    <iconify-icon icon="mdi:image-outline" class="text-[var(--primary)]"></iconify-icon>
                    Foto Produk
                </h2>

                <div class="relative group">
                    <div id="preview-container"
                         class="w-full aspect-square bg-gray-50 rounded-xl border-2 border-dashed border-gray-200 flex items-center justify-center overflow-hidden cursor-pointer hover:border-[var(--primary)] transition">
                        <div id="placeholder-content" class="text-center">
                            <iconify-icon icon="mdi:camera-plus-outline" class="text-5xl text-gray-300 mb-2"></iconify-icon>
                            <p class="text-xs text-gray-400 font-medium">Klik untuk upload</p>
                        </div>
                        <img id="image-preview" class="hidden w-full h-full object-contain p-2">
                    </div>
                    <input type="file" name="gambar" id="gambar-input" required
                           class="absolute inset-0 opacity-0 cursor-pointer"
                           accept="image/*">
                </div>

                <div class="space-y-1">
                    <p class="text-xs text-gray-500 font-medium flex items-center gap-2">
                        <iconify-icon icon="mdi:information-outline" class="text-sm"></iconify-icon>
                        Format: JPG, PNG, JPEG
                    </p>
                    <p class="text-xs text-gray-500 font-medium flex items-center gap-2">
                        <iconify-icon icon="mdi:file-outline" class="text-sm"></iconify-icon>
                        Ukuran maksimal: 2MB
                    </p>
                </div>

                <button type="button" id="remove-image"
                        class="hidden w-full px-4 py-2 rounded-lg bg-red-50 text-red-600 text-xs font-semibold hover:bg-red-100 transition">
                    <iconify-icon icon="mdi:trash-can-outline" class="text-sm"></iconify-icon>
                    Hapus Foto
                </button>

                {{-- Preview Info --}}
                <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                    <p class="text-xs font-semibold text-blue-700 mb-2 flex items-center gap-1">
                        <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                        Field yang akan tampil di detail produk:
                    </p>
                    <ul class="text-[11px] text-blue-600 space-y-1">
                        <li>✓ Product Description</li>
                        <li>✓ Indikasi Umum</li>
                        <li>✓ Komposisi</li>
                        <li>✓ Dosis</li>
                        <li>✓ Aturan Pakai</li>
                        <li>✓ Kemasan</li>
                        <li>✓ Manufaktur</li>
                        <li>✓ No. Registrasi</li>
                    </ul>
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
        preview.src = '';
        preview.classList.add('hidden');
        placeholder.classList.remove('hidden');
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
