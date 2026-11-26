<?php

namespace Tests\Modul4;

use App\Models\Obat;
use App\Models\PengumumanAdmin;
use App\Models\TargetOmzet;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

final class Modul4PengaturanProdukTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException('Pengujian Modul 4 memerlukan SQLite :memory:.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $this->withCookie('token', JWTAuth::fromUser($this->superadmin));
        $this->withoutVite();
    }

    public function test_target_omzet_valid_tersimpan_pada_bulan_berjalan(): void
    {
        // Arrange
        $periode = ['bulan' => now()->month, 'tahun' => now()->year];
        TargetOmzet::create($periode + ['target' => 10_000_000]);

        // Act
        $response = $this->put('/admin/settings/target', ['target' => 15_000_000]);

        // Assert
        $response->assertRedirect(route('admin.settings.index'))
            ->assertSessionHas('success', 'Target omzet berhasil disimpan.');
        $this->assertDatabaseCount('target_omzet', 1);
        $this->assertDatabaseHas('target_omzet', $periode + ['target' => 15_000_000]);
    }

    #[DataProvider('targetTidakValid')]
    public function test_target_omzet_tidak_valid_mempertahankan_target_lama(string|int $input): void
    {
        // Arrange
        $periode = ['bulan' => now()->month, 'tahun' => now()->year];
        TargetOmzet::create($periode + ['target' => 10_000_000]);

        // Act
        $response = $this->from('/admin/settings')->put('/admin/settings/target', ['target' => $input]);

        // Assert
        $response->assertRedirect(route('admin.settings.index'))->assertSessionHasErrors('target');
        $this->assertDatabaseCount('target_omzet', 1);
        $this->assertDatabaseHas('target_omzet', $periode + ['target' => 10_000_000]);
    }

    public static function targetTidakValid(): array
    {
        return [
            'kosong' => [''],
            'di bawah minimum' => [999_999],
            'di atas maksimum' => [30_000_001],
        ];
    }

    public function test_pengumuman_valid_disimpan_setelah_spasi_tepi_dipotong(): void
    {
        // Arrange
        $lama = $this->buatPengumumanAwal();
        $konten = 'Pelayanan apotek tetap dibuka setiap hari.';

        // Act
        $response = $this->put('/admin/settings/announcement', ['konten' => "  {$konten}  "]);

        // Assert
        $response->assertRedirect(route('admin.settings.index'))
            ->assertSessionHas('success', 'Pengumuman berhasil disimpan.');
        $this->assertDatabaseCount('pengumuman_admin', 1);
        $this->assertDatabaseHas('pengumuman_admin', [
            'id' => $lama->id,
            'konten' => $konten,
            'user_id' => $this->superadmin->id,
        ]);
    }

    #[DataProvider('pengumumanTidakValid')]
    public function test_pengumuman_tidak_valid_mempertahankan_pengumuman_lama(string $input): void
    {
        // Arrange
        $lama = $this->buatPengumumanAwal();

        // Act
        $response = $this->from('/admin/settings')->put('/admin/settings/announcement', ['konten' => $input]);

        // Assert
        $response->assertRedirect(route('admin.settings.index'))->assertSessionHasErrors('konten');
        $this->assertDatabaseCount('pengumuman_admin', 1);
        $this->assertDatabaseHas('pengumuman_admin', ['id' => $lama->id, 'konten' => $lama->konten]);
    }

    public static function pengumumanTidakValid(): array
    {
        return [
            'kosong' => [''],
            'kurang dari 20 karakter' => [str_repeat('A', 19)],
            'mengandung HTML' => ['<b>Pengumuman pengujian berformat HTML</b>'],
        ];
    }

    public function test_produk_baru_dengan_data_valid_dan_png_tersimpan(): void
    {
        // Arrange
        $disk = $this->fakePublicStorage();
        $data = $this->dataProduk();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a6S8AAAAASUVORK5CYII=');
        $data['gambar'] = UploadedFile::fake()->createWithContent('produk.png', $png);

        // Act
        $response = $this->from('/admin/obat/create')->post('/admin/obat', $data);

        // Assert
        $this->assertNull(session('error'), (string) session('error'));
        $response->assertRedirect(route('obat.index'))->assertSessionHas('success', 'Produk berhasil ditambah');
        $this->assertDatabaseHas('obat', ['nama' => $data['nama'], 'harga' => $data['harga'], 'stok' => $data['stok']]);
        $produk = Obat::query()->where('nama', $data['nama'])->firstOrFail();
        $this->assertDatabaseHas('deskripsi_obat', ['obat_id' => $produk->id, 'nilai' => $data['deskripsi']]);
        $disk->assertExists($produk->path_gambar);
    }

    public function test_tambah_produk_tanpa_gambar_ditolak(): void
    {
        // Arrange
        $data = $this->dataProduk();

        // Act
        $response = $this->from('/admin/obat/create')->post('/admin/obat', $data);

        // Assert
        $response->assertSessionHasErrors('gambar');
        $this->assertDatabaseMissing('obat', ['nama' => $data['nama']]);
    }

    public function test_produk_tersimpan_tampil_di_daftar_produk(): void
    {
        // Arrange
        $id = $this->buatProdukAwal();
        $nama = $this->dataProduk()['nama'];

        // Act
        $response = $this->get('/admin/obat');

        // Assert
        $response->assertOk()->assertSee($nama);
        $this->assertDatabaseHas('obat', ['id' => $id, 'nama' => $nama]);
    }

    public function test_ubah_produk_valid_memperbarui_harga_dan_stok(): void
    {
        // Arrange
        $id = $this->buatProdukAwal();
        $data = array_replace($this->dataProduk(), ['harga' => 120_000, 'stok' => 20]);

        // Act
        $response = $this->put('/admin/obat/'.$id, $data);

        // Assert
        $this->assertNull(session('error'), (string) session('error'));
        $response->assertRedirect(route('obat.index'))->assertSessionHas('success', 'Produk berhasil diperbarui');
        $this->assertDatabaseHas('obat', ['id' => $id, 'harga' => 120_000, 'stok' => 20]);
    }

    public function test_ubah_produk_dengan_harga_nol_ditolak(): void
    {
        // Arrange
        $id = $this->buatProdukAwal();
        $data = array_replace($this->dataProduk(), ['harga' => 0, 'stok' => 20]);

        // Act
        $response = $this->from('/admin/obat/'.$id.'/edit')->put('/admin/obat/'.$id, $data);

        // Assert
        $response->assertSessionHasErrors('harga');
        $this->assertDatabaseHas('obat', ['id' => $id, 'harga' => 100_000, 'stok' => 30]);
    }

    public function test_hapus_produk_bergambar_menghapus_data_dan_berkas_publik(): void
    {
        // Arrange
        $disk = $this->fakePublicStorage();
        $path = 'produk/uji-hapus.png';
        $disk->put($path, 'Data foto untuk pengujian');
        $id = $this->buatProdukAwal($path);

        // Act
        $response = $this->delete('/admin/obat/'.$id);

        // Assert
        $response->assertRedirect(route('obat.index'))->assertSessionHas('success', 'Produk berhasil dihapus');
        $this->assertDatabaseMissing('obat', ['id' => $id]);
        $disk->assertMissing($path);
    }

    private function buatPengumumanAwal(): PengumumanAdmin
    {
        return PengumumanAdmin::create([
            'konten' => 'Pengumuman lama untuk data pengujian.',
            'user_id' => $this->superadmin->id,
        ]);
    }

    private function dataProduk(): array
    {
        return [
            'nama' => 'Produk Uji Modul 4',
            'kategori' => 'Uji',
            'harga' => 100_000,
            'stok' => 30,
            'diskon_persen' => 0,
            'deskripsi' => 'Deskripsi produk untuk pengujian Modul 4.',
        ];
    }

    private function buatProdukAwal(?string $path = null): int
    {
        $data = $this->dataProduk();
        unset($data['deskripsi']);

        return DB::table('obat')->insertGetId($data + [
            'path_gambar' => $path,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function fakePublicStorage(): FilesystemAdapter
    {
        $this->app->useStoragePath(base_path('tmp/mod4_lab_8_fitur/runtime/pengaturan-produk'));

        return Storage::fake('public');
    }
}
