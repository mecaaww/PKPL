<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ObatController;
use App\Models\Obat;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

final class Modul4HapusProdukTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->useStoragePath(storage_path('framework/testing/modul4-hapus-produk'));
    }

    public function test_tc_01_produk_dengan_gambar_menghapus_file_dan_memanggil_delete(): void
    {
        // Arrange
        $disk = Storage::fake('public', ['throw' => true]);
        $path = 'produk/uji.png';
        $disk->put($path, 'isi berkas sintetis');
        $obat = Mockery::mock(Obat::class)->makePartial();
        $obat->setRawAttributes(['path_gambar' => $path]);
        $obat->expects('delete')->once()->andReturn(true);

        // Act
        $response = (new ObatController)->destroy($obat);

        // Assert
        $disk->assertMissing($path);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('obat.index'), $response->getTargetUrl());
        $this->assertSame('Produk berhasil dihapus', $response->getSession()->get('success'));
    }

    public function test_tc_02_produk_tanpa_gambar_tetap_memanggil_delete_dan_menjaga_file_lain(): void
    {
        // Arrange
        $disk = Storage::fake('public', ['throw' => true]);
        $gambarLain = 'produk/milik-produk-lain.png';
        $disk->put($gambarLain, 'isi berkas sintetis milik produk lain');
        $obat = Mockery::mock(Obat::class)->makePartial();
        $obat->setRawAttributes(['path_gambar' => null]);
        $obat->expects('delete')->once()->andReturn(true);

        // Act
        $response = (new ObatController)->destroy($obat);

        // Assert
        $disk->assertExists($gambarLain);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('obat.index'), $response->getTargetUrl());
        $this->assertSame('Produk berhasil dihapus', $response->getSession()->get('success'));
    }
}
