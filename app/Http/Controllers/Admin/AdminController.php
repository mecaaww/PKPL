<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetailPesanan;
use App\Models\Obat;
use App\Models\PengumumanAdmin;
use App\Models\Pesanan;
use App\Models\TargetOmzet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        // ── Available categories ──
        $categories = Obat::select('kategori')->whereNotNull('kategori')->distinct()->orderBy('kategori')->pluck('kategori');
        $selectedKategori = $request->query('kategori');
        if ($selectedKategori && !$categories->contains($selectedKategori)) {
            $selectedKategori = null;
        }

        // ── Date filter or period filter ──
        $selectedDate = $request->query('date');
        if ($selectedDate) {
            try {
                $targetDate = Carbon::createFromFormat('Y-m-d', $selectedDate);
            } catch (\Exception $e) {
                $targetDate = Carbon::today();
                $selectedDate = $targetDate->format('Y-m-d');
            }
            $period = 'custom';
            $periodStart = $targetDate->copy()->startOfDay();
            $periodEnd = $targetDate->copy()->endOfDay();
            $baseDate = $targetDate;
            $periodLabel = $targetDate->translatedFormat('d F Y');
            $periodSubtitle = 'pada ' . $targetDate->translatedFormat('d M Y');
        } else {
            $period = $request->query('period', 'week');
            $periodStart = $this->periodStart($period);
            $periodEnd = Carbon::now();
            $baseDate = Carbon::today();
            $periodLabel = match ($period) {
                'day'   => 'Hari Ini',
                'week'  => 'Minggu Ini',
                'month' => 'Bulan Ini',
                'year'  => 'Tahun Ini',
                default => 'Minggu Ini',
            };
            $periodSubtitle = match ($period) {
                'day'   => 'hari ini',
                'week'  => '7 hari terakhir',
                'month' => '30 hari terakhir',
                'year'  => '1 tahun terakhir',
                default => '7 hari terakhir',
            };
        }

        // ── Summary stats according to period & category ──
        $ordersInPeriodQuery = Pesanan::whereBetween('created_at', [$periodStart, $periodEnd]);

        if ($selectedKategori) {
            // Khusus kategori yang dipilih
            $totalPesanan = (clone $ordersInPeriodQuery)
                ->whereHas('detailPesanan.obat', fn($q) => $q->where('kategori', $selectedKategori))
                ->count();

            $totalPendapatan = DetailPesanan::whereHas('obat', fn($q) => $q->where('kategori', $selectedKategori))
                ->whereHas('pesanan', function ($q) use ($periodStart, $periodEnd) {
                    $q->where('status_pesanan', 'selesai')
                      ->whereBetween('created_at', [$periodStart, $periodEnd]);
                })
                ->sum('subtotal');

            $produkTerjual = DetailPesanan::whereHas('obat', fn($q) => $q->where('kategori', $selectedKategori))
                ->whereHas('pesanan', function ($q) use ($periodStart, $periodEnd) {
                    $q->where('status_pesanan', 'selesai')
                      ->whereBetween('created_at', [$periodStart, $periodEnd]);
                })
                ->sum('jumlah');

            $userAktif = (clone $ordersInPeriodQuery)
                ->whereHas('detailPesanan.obat', fn($q) => $q->where('kategori', $selectedKategori))
                ->distinct('user_id')
                ->count('user_id');

            $stokMenipis = Obat::where('kategori', $selectedKategori)->where('stok', '<', 10)->count();
        } else {
            // Semua kategori
            $totalPesanan = (clone $ordersInPeriodQuery)->count();

            $totalPendapatan = (clone $ordersInPeriodQuery)
                ->where('status_pesanan', 'selesai')
                ->sum('total_harga');

            $produkTerjual = DetailPesanan::whereHas('pesanan', function ($q) use ($periodStart, $periodEnd) {
                $q->where('status_pesanan', 'selesai')
                  ->whereBetween('created_at', [$periodStart, $periodEnd]);
            })->sum('jumlah');

            $userAktif = (clone $ordersInPeriodQuery)
                ->distinct('user_id')
                ->count('user_id');

            $stokMenipis = Obat::where('stok', '<', 10)->count();
        }

        $totalPelangganSemua = User::where('role', 'pelanggan')->count();

        $stats = [
            'total_pesanan'       => (int) $totalPesanan,
            'pendapatan'          => (float) $totalPendapatan,
            'obat_stok'           => (int) $stokMenipis,
            'user_aktif'          => (int) $userAktif,
            'total_pelanggan_all' => (int) $totalPelangganSemua,
            'produk_terjual'      => (int) $produkTerjual,
        ];

        // ── Weekly chart (7 hari berurutan hingga baseDate) ──
        $dates = collect();
        for ($i = 6; $i >= 0; $i--) {
            $d = $baseDate->copy()->subDays($i);
            $dates->put($d->format('Y-m-d'), [
                'label' => $d->translatedFormat('d M'),
                'total' => 0,
            ]);
        }

        $chartStart = $baseDate->copy()->subDays(6)->startOfDay();
        $chartEnd = $baseDate->copy()->endOfDay();

        $recentOrdersQuery = Pesanan::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as total')
        )
        ->whereBetween('created_at', [$chartStart, $chartEnd]);

        if ($selectedKategori) {
            $recentOrdersQuery->whereHas('detailPesanan.obat', fn($q) => $q->where('kategori', $selectedKategori));
        }

        $recentOrders = $recentOrdersQuery->groupBy('date')->get();

        foreach ($recentOrders as $ro) {
            if ($dates->has($ro->date)) {
                $item = $dates->get($ro->date);
                $item['total'] = (int) $ro->total;
                $dates->put($ro->date, $item);
            }
        }

        $chartData = $dates->map(function ($val) {
            return (object) [
                'date'  => $val['label'],
                'total' => $val['total'],
            ];
        })->values();

        // ── Top-selling products in selected period & category ──
        $topProductsQuery = DetailPesanan::select(
            'detail_pesanan.obat_id',
            DB::raw('SUM(detail_pesanan.jumlah) as total_terjual'),
            DB::raw('SUM(detail_pesanan.subtotal) as total_pendapatan')
        )
        ->whereHas('pesanan', function ($q) use ($periodStart, $periodEnd) {
            $q->where('status_pesanan', 'selesai')
              ->whereBetween('created_at', [$periodStart, $periodEnd]);
        });

        if ($selectedKategori) {
            $topProductsQuery->whereHas('obat', fn($q) => $q->where('kategori', $selectedKategori));
        }

        $topProducts = $topProductsQuery
            ->groupBy('detail_pesanan.obat_id')
            ->orderByDesc('total_terjual')
            ->take(10)
            ->with('obat:id,nama,kategori,path_gambar,harga,stok')
            ->get();

        // ── Sales chart for the period & category ──
        $salesChartQuery = DetailPesanan::join('pesanan', 'detail_pesanan.pesanan_id', '=', 'pesanan.id')
            ->join('obat', 'detail_pesanan.obat_id', '=', 'obat.id')
            ->where('pesanan.status_pesanan', 'selesai')
            ->whereBetween('pesanan.created_at', [$periodStart, $periodEnd]);

        if ($selectedKategori) {
            $salesChartQuery->where('obat.kategori', $selectedKategori);
        }

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        if ($selectedDate || $period === 'day') {
            $labelExpr = $isSqlite ? "strftime('%H:00', pesanan.created_at) as label" : "DATE_FORMAT(pesanan.created_at, '%H:00') as label";
            $salesChart = (clone $salesChartQuery)
                ->select(
                    DB::raw($labelExpr),
                    DB::raw('SUM(detail_pesanan.jumlah) as total')
                )
                ->groupBy('label')
                ->orderBy('label', 'ASC')
                ->get();
        } elseif ($period === 'year') {
            $labelExpr = $isSqlite ? "strftime('%m-%Y', pesanan.created_at) as label" : "DATE_FORMAT(pesanan.created_at, '%b %Y') as label";
            $sortExpr = $isSqlite ? "strftime('%Y-%m', pesanan.created_at) as sort_key" : "DATE_FORMAT(pesanan.created_at, '%Y-%m') as sort_key";
            $salesChart = (clone $salesChartQuery)
                ->select(
                    DB::raw($labelExpr),
                    DB::raw($sortExpr),
                    DB::raw('SUM(detail_pesanan.jumlah) as total')
                )
                ->groupBy('sort_key', 'label')
                ->orderBy('sort_key', 'ASC')
                ->get();
        } else {
            $labelExpr = $isSqlite ? "strftime('%d-%m', pesanan.created_at) as label" : "DATE_FORMAT(pesanan.created_at, '%d %b') as label";
            $sortExpr = $isSqlite ? "date(pesanan.created_at) as sort_key" : "DATE(pesanan.created_at) as sort_key";
            $salesChart = (clone $salesChartQuery)
                ->select(
                    DB::raw($labelExpr),
                    DB::raw($sortExpr),
                    DB::raw('SUM(detail_pesanan.jumlah) as total')
                )
                ->groupBy('sort_key', 'label')
                ->orderBy('sort_key', 'ASC')
                ->get();
        }

        // ── Target Omzet Bulan Berjalan ──
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $targetOmzet = TargetOmzet::where('bulan', $currentMonth)
            ->where('tahun', $currentYear)
            ->first();

        $pendapatanBulanBerjalan = (float) Pesanan::where('status_pesanan', 'selesai')
            ->whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth)
            ->sum('total_harga');

        $hasTarget = $targetOmzet !== null && $targetOmzet->target > 0;
        $persentase = $hasTarget ? round(($pendapatanBulanBerjalan / $targetOmzet->target) * 100, 1) : 0;

        $targetOmzetData = [
            'has_target'   => $hasTarget,
            'target'       => $targetOmzet ? (float) $targetOmzet->target : 0,
            'pendapatan'   => $pendapatanBulanBerjalan,
            'persentase'   => $persentase,
            'progress_bar' => min(100, $persentase),
            'is_achieved'  => $hasTarget && ($pendapatanBulanBerjalan >= $targetOmzet->target),
            'bulan_label'  => Carbon::now()->translatedFormat('F Y'),
        ];

        // ── Pengumuman Aktif ──
        $pengumumanAktif = PengumumanAdmin::latest()->first();

        return view('admins.dashboard', compact(
            'stats', 'chartData', 'topProducts', 'salesChart', 'period', 'selectedDate', 'periodLabel', 'periodSubtitle', 'categories', 'selectedKategori', 'targetOmzetData', 'pengumumanAktif'
        ));
    }

    private function periodStart(string $period): Carbon
    {
        return match ($period) {
            'day'   => Carbon::today(),
            'week'  => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            'year'  => Carbon::now()->subYear(),
            default => Carbon::now()->subWeek(),
        };
    }
}
