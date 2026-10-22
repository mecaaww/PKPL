@extends('layouts.admin')

@section('title', 'Dashboard Admin – Alfina Rizqy')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between sm:items-end gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Dashboard Utama</h1>
            <p class="text-gray-500 text-sm mt-1">Monitoring performa apotek secara real-time</p>
        </div>
        <div class="bg-white px-4 py-2 rounded-xl shadow-sm border border-gray-200 flex items-center gap-2">
            <iconify-icon icon="mdi:calendar-check" class="text-[var(--primary)]"></iconify-icon>
            <span class="text-xs font-semibold text-gray-600">{{ date('d F Y') }}</span>
        </div>
    </div>

    {{-- Baris Pengaturan: Target Omzet & Pengumuman Admin --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Card Target Omzet Bulanan --}}
        <div class="bg-white p-5 rounded-xl shadow-md border border-gray-200 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shadow-sm">
                            <iconify-icon icon="mdi:target"></iconify-icon>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800">Target Omzet Bulanan</h3>
                            <p class="text-[11px] text-gray-400">Bulan {{ $targetOmzetData['bulan_label'] }}</p>
                        </div>
                    </div>
                    @if($targetOmzetData['has_target'])
                        @if($targetOmzetData['is_achieved'])
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                            <iconify-icon icon="mdi:check-circle" class="text-sm"></iconify-icon>
                            Target Tercapai
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                            <iconify-icon icon="mdi:clock-outline" class="text-sm"></iconify-icon>
                            Belum Tercapai
                        </span>
                        @endif
                    @endif
                </div>

                @if($targetOmzetData['has_target'])
                <div class="space-y-3 mt-4">
                    <div class="flex items-baseline justify-between">
                        <div>
                            <p class="text-xs text-gray-400 font-medium">Pendapatan Bulan Berjalan</p>
                            <p class="text-xl font-extrabold text-gray-800">
                                Rp{{ number_format($targetOmzetData['pendapatan'], 0, ',', '.') }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-400 font-medium">Target Omzet</p>
                            <p class="text-sm font-bold text-gray-600">
                                Rp{{ number_format($targetOmzetData['target'], 0, ',', '.') }}
                            </p>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div>
                        <div class="flex justify-between text-xs font-semibold mb-1">
                            <span class="text-gray-500">Pencapaian:</span>
                            <span class="{{ $targetOmzetData['is_achieved'] ? 'text-green-600' : 'text-blue-600' }} font-bold">
                                {{ $targetOmzetData['persentase'] }}%
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden p-0.5 border border-slate-200">
                            <div class="h-full rounded-full transition-all duration-500 {{ $targetOmzetData['is_achieved'] ? 'bg-gradient-to-r from-emerald-500 to-green-500' : 'bg-gradient-to-r from-blue-500 to-indigo-500' }}"
                                 style="width: {{ $targetOmzetData['progress_bar'] }}%;"></div>
                        </div>
                    </div>
                </div>
                @else
                <div class="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-200 text-center my-3">
                    <iconify-icon icon="mdi:information-outline" class="text-2xl text-slate-400 mb-1"></iconify-icon>
                    <p class="text-xs text-gray-500 font-medium">
                        Superadmin belum menentukan target omzet bulan ini.
                    </p>
                    @if(auth()->guard('api')->user()?->isSuperadmin())
                    <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[var(--primary)] hover:underline mt-2">
                        <span>Atur Target Sekarang</span>
                        <iconify-icon icon="mdi:arrow-right"></iconify-icon>
                    </a>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- Card Pengumuman Admin --}}
        <div class="bg-white p-5 rounded-xl shadow-md border border-gray-200 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-lg shadow-sm">
                            <iconify-icon icon="mdi:bullhorn-outline"></iconify-icon>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800">Pengumuman Admin</h3>
                            <p class="text-[11px] text-gray-400">Pesan dari Superadmin</p>
                        </div>
                    </div>
                    @if(auth()->guard('api')->user()?->isSuperadmin())
                    <a href="{{ route('admin.settings.index') }}" class="text-xs text-gray-400 hover:text-[var(--primary)] flex items-center gap-1 font-semibold transition-colors">
                        <iconify-icon icon="mdi:pencil-outline"></iconify-icon>
                        <span>Kelola</span>
                    </a>
                    @endif
                </div>

                @if($pengumumanAktif)
                <div class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-100 my-2">
                    <p class="text-xs text-slate-700 leading-relaxed font-medium">
                        {{ $pengumumanAktif->konten }}
                    </p>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-400 pt-1">
                    <span>Terakhir diperbarui:</span>
                    <span class="font-semibold text-gray-500">{{ $pengumumanAktif->updated_at->translatedFormat('d F Y, H:i') }} WIB</span>
                </div>
                @else
                <div class="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-200 text-center my-3">
                    <iconify-icon icon="mdi:bullhorn-variant-outline" class="text-2xl text-slate-400 mb-1"></iconify-icon>
                    <p class="text-xs text-gray-500 font-medium">
                        Belum ada pengumuman aktif dari superadmin.
                    </p>
                    @if(auth()->guard('api')->user()?->isSuperadmin())
                    <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[var(--primary)] hover:underline mt-2">
                        <span>Tulis Pengumuman</span>
                        <iconify-icon icon="mdi:arrow-right"></iconify-icon>
                    </a>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Filter Bar: Kategori, Periode & Kalender --}}
    <div class="bg-white p-4 rounded-xl shadow-md border border-gray-200 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        {{-- Kategori Filter --}}
        <div class="flex items-center gap-1.5 flex-wrap">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-1 mr-1">
                <iconify-icon icon="mdi:shape-outline" class="text-base text-[var(--primary)]"></iconify-icon>
                Kategori:
            </span>
            <a href="{{ route('admin.dashboard', array_filter(['period' => $period !== 'custom' ? $period : null, 'date' => $selectedDate])) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all {{ !$selectedKategori ? 'bg-[var(--primary)] text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                Semua
            </a>
            @foreach($categories as $cat)
            <a href="{{ route('admin.dashboard', array_filter(['kategori' => $cat, 'period' => $period !== 'custom' ? $period : null, 'date' => $selectedDate])) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all {{ $selectedKategori === $cat ? 'bg-[var(--primary)] text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $cat }}
            </a>
            @endforeach
        </div>

        {{-- Periode & Kalender --}}
        <div class="flex items-center gap-2 flex-wrap">
            <div class="flex gap-1 bg-gray-100 p-1 rounded-xl">
                @foreach(['day' => 'Hari Ini', 'week' => 'Minggu', 'month' => 'Bulan', 'year' => 'Tahun'] as $key => $label)
                <a href="{{ route('admin.dashboard', array_filter(['period' => $key, 'kategori' => $selectedKategori])) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all {{ (!$selectedDate && $period === $key) ? 'bg-white text-[var(--primary)] shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>

            {{-- Kalender Picker --}}
            <form id="dateFilterForm" action="{{ route('admin.dashboard') }}" method="GET" class="relative">
                @if($selectedKategori)
                <input type="hidden" name="kategori" value="{{ $selectedKategori }}">
                @endif
                <div class="relative bg-white px-3 py-1.5 rounded-xl border border-gray-200 flex items-center gap-2 hover:border-[var(--primary)] transition-all cursor-pointer group">
                    <iconify-icon icon="mdi:calendar-month-outline" class="text-[var(--primary)] text-base"></iconify-icon>
                    <span class="text-xs font-semibold text-gray-700 select-none">
                        {{ $selectedDate ? \Carbon\Carbon::parse($selectedDate)->translatedFormat('d M Y') : 'Pilih Tanggal' }}
                    </span>
                    <iconify-icon icon="mdi:chevron-down" class="text-gray-400 group-hover:text-gray-600 text-xs transition-colors"></iconify-icon>
                    <input type="date" 
                           id="dashboardDatePicker" 
                           name="date" 
                           value="{{ $selectedDate ?? date('Y-m-d') }}" 
                           max="{{ date('Y-m-d') }}"
                           onchange="document.getElementById('dateFilterForm').submit()" 
                           title="Klik untuk memilih tanggal"
                           class="opacity-0 absolute inset-0 w-full h-full cursor-pointer z-10">
                </div>
            </form>

            @if($selectedDate || $selectedKategori)
            <a href="{{ route('admin.dashboard') }}" 
               title="Reset semua filter" 
               class="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-semibold flex items-center gap-1 transition-all">
                <iconify-icon icon="mdi:close-circle" class="text-sm text-gray-400"></iconify-icon>
                <span>Reset</span>
            </a>
            @endif
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        {{-- Card Pesanan --}}
        <div class="bg-white p-5 rounded-xl shadow-md border border-gray-200 flex items-center gap-4 hover:shadow-lg transition-all">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-2xl">
                <iconify-icon icon="mdi:shopping-outline"></iconify-icon>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold">Pesanan</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['total_pesanan'] }}</p>
                <p class="text-[10px] text-gray-400 font-medium">
                    {{ $periodSubtitle }}
                </p>
            </div>
        </div>

        {{-- Card Pendapatan --}}
        <div class="bg-white p-5 rounded-xl shadow-md border border-gray-200 flex items-center gap-4 hover:shadow-lg transition-all">
            <div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center text-2xl">
                <iconify-icon icon="mdi:wallet-outline"></iconify-icon>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold">Pendapatan</p>
                <p class="text-xl font-bold text-green-600">Rp{{ number_format($stats['pendapatan'], 0, ',', '.') }}</p>
                <p class="text-[10px] text-gray-400 font-medium">
                    {{ $periodSubtitle }}{{ $selectedKategori ? ' (' . $selectedKategori . ')' : '' }}
                </p>
            </div>
        </div>

        {{-- Produk Terjual Card --}}
        <div class="bg-white p-5 rounded-xl shadow-md border border-gray-200 flex items-center gap-4 hover:shadow-lg transition-all">
            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-2xl">
                <iconify-icon icon="mdi:package-variant-closed-check"></iconify-icon>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold">Produk Terjual</p>
                <p class="text-2xl font-bold text-indigo-600">{{ $stats['produk_terjual'] }}</p>
                <p class="text-[10px] text-gray-400 font-medium">
                    {{ $periodSubtitle }}{{ $selectedKategori ? ' (' . $selectedKategori . ')' : '' }}
                </p>
            </div>
        </div>

        {{-- Stok Menipis Card --}}
        <div class="bg-white p-5 rounded-xl shadow-md border border-gray-200 flex items-center gap-4 hover:shadow-lg transition-all">
            <div class="w-12 h-12 bg-red-50 text-red-600 rounded-xl flex items-center justify-center text-2xl">
                <iconify-icon icon="mdi:alert-decagram-outline"></iconify-icon>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold">Stok Menipis</p>
                <p class="text-2xl font-bold text-red-600">{{ $stats['obat_stok'] }}</p>
                <p class="text-[10px] text-gray-400 font-medium">
                    {{ $selectedKategori ? 'Kategori ' . $selectedKategori : 'Stok < 10 (Semua)' }}
                </p>
            </div>
        </div>

        {{-- Pelanggan Card --}}
        <div class="bg-white p-5 rounded-xl shadow-md border border-gray-200 flex items-center gap-4 hover:shadow-lg transition-all">
            <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center text-2xl">
                <iconify-icon icon="mdi:account-heart-outline"></iconify-icon>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold">Pelanggan Aktif</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['user_aktif'] }}</p>
                <p class="text-[10px] text-gray-400 font-medium" title="Pelanggan aktif dalam periode ini dari total {{ $stats['total_pelanggan_all'] }} akun">
                    {{ $periodSubtitle }} (total: {{ $stats['total_pelanggan_all'] }})
                </p>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Weekly Orders Chart --}}
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-gray-800 flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-500 text-white rounded-xl flex items-center justify-center shadow-md">
                        <iconify-icon icon="mdi:chart-box-outline" class="text-xl"></iconify-icon>
                    </div>
                    <span>Grafik Penjualan</span>
                </h3>
                <span class="text-xs font-semibold text-gray-500 border px-3 py-1 rounded-full">
                    {{ $selectedDate ? ('7 Hari s/d ' . \Carbon\Carbon::parse($selectedDate)->translatedFormat('d M')) : '7 Hari Terakhir' }}
                </span>
            </div>
            <div class="h-[280px]">
                <canvas id="orderChart"></canvas>
            </div>
        </div>

        {{-- Products Sold Chart --}}
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-gray-800 flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-500 text-white rounded-xl flex items-center justify-center shadow-md">
                        <iconify-icon icon="mdi:chart-bar" class="text-xl"></iconify-icon>
                    </div>
                    <span>Produk Terjual</span>
                </h3>
                <div class="flex items-center gap-1.5">
                    @if($selectedKategori)
                    <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 border border-indigo-200 px-2.5 py-1 rounded-full">
                        {{ $selectedKategori }}
                    </span>
                    @endif
                    <span class="text-xs font-semibold text-gray-500 border px-3 py-1 rounded-full">
                        {{ $periodLabel }}
                    </span>
                </div>
            </div>
            <div class="h-[280px]">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Produk Terjual Section --}}
    <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-gray-800 flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-500 text-white rounded-xl flex items-center justify-center shadow-md">
                            <iconify-icon icon="mdi:trophy-outline" class="text-xl"></iconify-icon>
                        </div>
                        Produk Terlaris
                    </h3>
                    <p class="text-xs text-gray-400 mt-1 pl-[52px]">
                        Periode: <span class="font-semibold text-gray-600">{{ $periodLabel }}</span>
                        @if($selectedKategori)
                        • Kategori: <span class="font-semibold text-indigo-600">{{ $selectedKategori }}</span>
                        @endif
                    </p>
                </div>

                {{-- Status active filter pill --}}
                <div class="flex items-center gap-2">
                    @if($selectedKategori)
                    <span class="px-3 py-1.5 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-600 border border-indigo-200 flex items-center gap-1">
                        <iconify-icon icon="mdi:filter-outline" class="text-sm"></iconify-icon>
                        {{ $selectedKategori }}
                    </span>
                    @endif
                    @if($selectedDate)
                    <span class="px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center gap-1">
                        <iconify-icon icon="mdi:calendar-check" class="text-sm"></iconify-icon>
                        {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d M Y') }}
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="text-left px-6 py-4 w-12">#</th>
                        <th class="text-left px-6 py-4">Produk</th>
                        <th class="text-left px-6 py-4">Kategori</th>
                        <th class="text-center px-6 py-4">Stok</th>
                        <th class="text-center px-6 py-4">Terjual</th>
                        <th class="text-right px-6 py-4">Pendapatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($topProducts as $i => $item)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-6 py-4">
                            @if($i < 3)
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold text-white {{ $i === 0 ? 'bg-amber-400' : ($i === 1 ? 'bg-slate-400' : 'bg-amber-700') }}">
                                {{ $i + 1 }}
                            </div>
                            @else
                            <span class="text-gray-400 font-semibold pl-1.5">{{ $i + 1 }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if($item->obat && $item->obat->path_gambar)
                                <img src="{{ asset('storage/' . $item->obat->path_gambar) }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-gray-200">
                                @else
                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                    <iconify-icon icon="mdi:pill" class="text-gray-400"></iconify-icon>
                                </div>
                                @endif
                                <span class="font-semibold text-gray-800 line-clamp-1">{{ $item->obat->nama ?? 'Produk Dihapus' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-600">
                                {{ $item->obat->kategori ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($item->obat && $item->obat->stok < 10)
                            <span class="text-red-600 font-bold">{{ $item->obat->stok }}</span>
                            @else
                            <span class="text-gray-600 font-semibold">{{ $item->obat->stok ?? '-' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-600">
                                <iconify-icon icon="mdi:arrow-up-bold" class="text-sm"></iconify-icon>
                                {{ number_format($item->total_terjual) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-green-600">
                            Rp{{ number_format($item->total_pendapatan, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center">
                                    <iconify-icon icon="mdi:chart-line-variant" class="text-3xl text-gray-400"></iconify-icon>
                                </div>
                                <p class="text-gray-500 font-medium">Belum ada produk terjual pada periode ini.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Weekly orders chart
        const orderCanvas = document.getElementById('orderChart');
        if (orderCanvas) {
            const ctxOrder = orderCanvas.getContext('2d');
            const gradientOrder = ctxOrder.createLinearGradient(0, 0, 0, 260);
            gradientOrder.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
            gradientOrder.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

            new Chart(ctxOrder, {
                type: 'line',
                data: {
                    labels: {!! json_encode($chartData->pluck('date')) !!},
                    datasets: [{
                        label: 'Pesanan',
                        data: {!! json_encode($chartData->pluck('total')) !!},
                        borderColor: '#10b981',
                        backgroundColor: gradientOrder,
                        borderWidth: 3,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#10b981',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            padding: 10,
                            titleFont: { size: 12, weight: 'bold' },
                            bodyFont: { size: 12 },
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' Pesanan';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                font: { weight: '600' },
                                color: '#94a3b8',
                                precision: 0
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { weight: '600' }, color: '#94a3b8' }
                        }
                    }
                }
            });
        }

        // Products sold chart
        const salesCanvas = document.getElementById('salesChart');
        if (salesCanvas) {
            const ctxSales = salesCanvas.getContext('2d');
            const gradientSales = ctxSales.createLinearGradient(0, 0, 0, 260);
            gradientSales.addColorStop(0, 'rgba(99, 102, 241, 0.9)');
            gradientSales.addColorStop(1, 'rgba(129, 140, 248, 0.4)');

            new Chart(ctxSales, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($salesChart->pluck('label')) !!},
                    datasets: [{
                        label: 'Produk Terjual',
                        data: {!! json_encode($salesChart->pluck('total')) !!},
                        backgroundColor: gradientSales,
                        borderColor: 'rgba(99, 102, 241, 1)',
                        borderWidth: 1,
                        borderRadius: 6,
                        borderSkipped: false,
                        maxBarThickness: 32,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            padding: 10,
                            titleFont: { size: 12, weight: 'bold' },
                            bodyFont: { size: 12 },
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' item terjual';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                font: { weight: '600' },
                                color: '#94a3b8',
                                precision: 0
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { weight: '600', size: 10 },
                                color: '#94a3b8',
                                maxRotation: 45,
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
