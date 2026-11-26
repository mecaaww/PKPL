<?php

namespace Tests\Feature;

use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class Modul4PesananDashboardTest extends TestCase
{
    use RefreshDatabase;

    private const STATUS_AWAL = 'menunggu pembayaran';

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Tes Modul 4 memerlukan SQLite :memory:.');
        }
    }

    public function test_rq_007_status_valid_tersimpan_dan_menampilkan_pesan_sukses(): void
    {
        // Arrange
        $this->signInAsSuperadmin();
        $pesanan = $this->createOrderForStatusChange();

        // Act
        $response = $this->from(route('admin.pesanan.show', $pesanan->id))
            ->put(route('admin.pesanan.updateStatus', $pesanan->id), ['status' => 'dikemas']);

        // Assert
        $response->assertRedirect(route('admin.pesanan.show', $pesanan->id));
        $response->assertSessionHas('success', 'Status pesanan #INV-M4-007 berhasil diubah menjadi dikemas');
        $this->assertDatabaseHas('pesanan', ['id' => $pesanan->id, 'status_pesanan' => 'dikemas']);
    }

    public function test_rq_007_status_tidak_dikenal_masih_diterima_kesenjangan_validasi(): void
    {
        // Arrange
        $this->signInAsSuperadmin();
        $pesanan = $this->createOrderForStatusChange();
        $statusTidakValid = 'status-tidak-ada';

        // Act
        $response = $this->from(route('admin.pesanan.show', $pesanan->id))
            ->put(route('admin.pesanan.updateStatus', $pesanan->id), ['status' => $statusTidakValid]);

        // Assert: mencatat celah validasi yang ada pada INC-M3-002.
        $response->assertRedirect(route('admin.pesanan.show', $pesanan->id));
        $response->assertSessionDoesntHaveErrors(['status']);
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('pesanan', ['id' => $pesanan->id, 'status_pesanan' => $statusTidakValid]);
    }

    public function test_rq_007_status_kosong_menjadi_kesalahan_basis_data(): void
    {
        // Arrange
        $this->signInAsSuperadmin();
        $pesanan = $this->createOrderForStatusChange();

        // Act
        $response = $this->from(route('admin.pesanan.show', $pesanan->id))
            ->put(route('admin.pesanan.updateStatus', $pesanan->id), ['status' => '']);

        // Assert: mendokumentasikan HTTP 500 tanpa pesan validasi status.
        $response->assertInternalServerError();
        $response->assertSessionDoesntHaveErrors(['status']);
        $this->assertInstanceOf(QueryException::class, $response->exceptions->first());
        $this->assertDatabaseHas('pesanan', ['id' => $pesanan->id, 'status_pesanan' => self::STATUS_AWAL]);
    }

    #[DataProvider('dashboardFilters')]
    public function test_rq_008_filter_dashboard_menghitung_ringkasan_sesuai_kategori_dan_periode(
        array $filter,
        ?string $kategoriEfektif,
        int $jumlahPesanan,
        float $pendapatan,
    ): void {
        // Arrange
        $this->travelTo(Carbon::parse('2026-11-24 12:00:00'));
        $this->signInAsSuperadmin();
        $this->createDashboardOrders();

        // Act
        $response = $this->get(route('admin.dashboard', $filter));

        // Assert
        $response->assertOk()->assertViewIs('admins.dashboard');
        $stats = $response->viewData('stats');
        $this->assertSame($kategoriEfektif, $response->viewData('selectedKategori'));
        $this->assertSame('Minggu Ini', $response->viewData('periodLabel'));
        $this->assertSame($jumlahPesanan, $stats['total_pesanan']);
        $this->assertSame($pendapatan, $stats['pendapatan']);
    }

    public static function dashboardFilters(): array
    {
        return [
            'kategori UJI dan periode minggu' => [['kategori' => 'UJI', 'period' => 'week'], 'UJI', 1, 100.0],
            'kategori tidak dikenal memakai semua kategori' => [['kategori' => 'TIDAK_ADA', 'period' => 'week'], null, 2, 300.0],
            'periode tidak dikenal memakai minggu' => [['kategori' => 'UJI', 'period' => 'abc'], 'UJI', 1, 100.0],
        ];
    }

    private function signInAsSuperadmin(): void
    {
        $superadmin = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $this->withCookie('token', JWTAuth::fromUser($superadmin));
    }

    private function createOrderForStatusChange(): Pesanan
    {
        $pelanggan = User::factory()->create(['role' => User::ROLE_PELANGGAN]);

        return Pesanan::create([
            'nomor_invoice' => 'INV-M4-007',
            'user_id' => $pelanggan->id,
            'total_harga' => 100000,
            'status_pesanan' => self::STATUS_AWAL,
        ]);
    }

    private function createDashboardOrders(): void
    {
        $pelanggan = User::factory()->create(['role' => User::ROLE_PELANGGAN]);
        $obatUji = $this->createDashboardProduct('Produk Uji', 'UJI', 100);
        $obatLain = $this->createDashboardProduct('Produk Lain', 'LAIN', 200);
        $pesananDashboard = [
            ['M4-UJI-BARU', $obatUji, 100, now()->subDay()],
            ['M4-LAIN-BARU', $obatLain, 200, now()->subDays(2)],
            ['M4-UJI-LAMA', $obatUji, 900, now()->subMonths(2)],
        ];

        foreach ($pesananDashboard as [$nomorInvoice, $obatId, $jumlah, $tanggal]) {
            $pesananId = DB::table('pesanan')->insertGetId([
                'nomor_invoice' => $nomorInvoice,
                'user_id' => $pelanggan->id,
                'total_harga' => $jumlah,
                'status_pesanan' => 'selesai',
                'created_at' => $tanggal,
                'updated_at' => $tanggal,
            ]);
            DB::table('detail_pesanan')->insert([
                'pesanan_id' => $pesananId,
                'obat_id' => $obatId,
                'jumlah' => 1,
                'harga_satuan_asli' => $jumlah,
                'persentase_diskon' => 0,
                'harga_setelah_diskon' => $jumlah,
                'subtotal' => $jumlah,
                'created_at' => $tanggal,
                'updated_at' => $tanggal,
            ]);
        }
    }

    private function createDashboardProduct(string $nama, string $kategori, int $harga): int
    {
        return DB::table('obat')->insertGetId([
            'nama' => $nama,
            'kategori' => $kategori,
            'harga' => $harga,
            'stok' => 5,
            'diskon_persen' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
