<?php

namespace Database\Seeders;

use App\Models\DetailPesanan;
use App\Models\Obat;
use App\Models\Pesanan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PesananSeeder extends Seeder
{
    public function run(): void
    {
        $pelangganIds = User::where('role', 'pelanggan')->pluck('id')->toArray();

        if (empty($pelangganIds)) {
            $this->command->error('Tidak ada pelanggan. Buat akun pelanggan terlebih dahulu.');
            return;
        }

        // Ambil produk yang stoknya > 0
        $products = Obat::where('stok', '>', 0)->get();

        if ($products->isEmpty()) {
            $this->command->error('Tidak ada produk dengan stok tersedia.');
            return;
        }

        $statusOptions = ['selesai', 'selesai', 'selesai', 'selesai', 'dikirim', 'dikemas', 'menunggu pembayaran', 'dibatalkan'];
        $metodePembayaran = ['Transfer Bank', 'COD', 'E-Wallet', 'QRIS'];

        $alamatList = [
            'Jl. Merdeka No. 12, Kota Malang',
            'Jl. Soekarno Hatta No. 45, Kota Malang',
            'Jl. Veteran No. 8, Kota Malang',
            'Jl. Blimbing Indah No. 23, Kota Malang',
            'Jl. Sulfat No. 100, Kota Malang',
            'Jl. Kawi No. 33, Kota Malang',
            'Jl. Ijen No. 55, Kota Malang',
            'Jl. Dieng No. 17, Kota Malang',
        ];

        $totalCreated = 0;

        // Buat pesanan tersebar 30 hari terakhir
        for ($dayOffset = 30; $dayOffset >= 0; $dayOffset--) {
            $date = Carbon::now()->subDays($dayOffset);

            // Jumlah pesanan per hari bervariasi (1-5 pesanan)
            $ordersPerDay = rand(1, 5);

            for ($o = 0; $o < $ordersPerDay; $o++) {
                $userId = $pelangganIds[array_rand($pelangganIds)];
                $status = $statusOptions[array_rand($statusOptions)];
                $metode = $metodePembayaran[array_rand($metodePembayaran)];
                $alamat = $alamatList[array_rand($alamatList)];
                $ongkir = [0, 5000, 8000, 10000, 12000, 15000][array_rand([0, 5000, 8000, 10000, 12000, 15000])];

                // Waktu acak dalam hari tersebut
                $createdAt = $date->copy()
                    ->setHour(rand(7, 21))
                    ->setMinute(rand(0, 59))
                    ->setSecond(rand(0, 59));

                $invoiceNumber = 'INV-' . $createdAt->format('Ymd') . '-' . str_pad($totalCreated + 1, 4, '0', STR_PAD_LEFT);

                // Buat pesanan dulu dengan total sementara
                $pesanan = Pesanan::create([
                    'nomor_invoice'      => $invoiceNumber,
                    'user_id'            => $userId,
                    'latitude'           => -7.9 + (rand(-100, 100) / 10000),
                    'longitude'          => 112.6 + (rand(-100, 100) / 10000),
                    'alamat_lengkap'     => $alamat,
                    'detail_alamat'      => 'RT ' . rand(1, 15) . '/RW ' . rand(1, 10),
                    'ongkir'             => $ongkir,
                    'total_harga'        => 0, // akan di-update
                    'status_pesanan'     => $status,
                    'metode_pembayaran'  => $metode,
                    'created_at'         => $createdAt,
                    'updated_at'         => $createdAt,
                ]);

                // Tambahkan 1-4 item per pesanan
                $itemCount = rand(1, 4);
                $selectedProducts = $products->random(min($itemCount, $products->count()));
                $totalHarga = 0;

                foreach ($selectedProducts as $product) {
                    $jumlah = rand(1, 3);
                    $hargaAsli = $product->harga;
                    $diskon = (int) ($product->diskon_persen ?? 0);
                    $hargaDiskon = (int) round($hargaAsli * (1 - $diskon / 100));
                    $subtotal = $hargaDiskon * $jumlah;

                    DetailPesanan::create([
                        'pesanan_id'          => $pesanan->id,
                        'obat_id'             => $product->id,
                        'jumlah'              => $jumlah,
                        'harga_satuan_asli'   => $hargaAsli,
                        'persentase_diskon'   => $diskon,
                        'harga_setelah_diskon' => $hargaDiskon,
                        'subtotal'            => $subtotal,
                        'created_at'          => $createdAt,
                        'updated_at'          => $createdAt,
                    ]);

                    $totalHarga += $subtotal;
                }

                $pesanan->update(['total_harga' => $totalHarga + $ongkir]);
                $totalCreated++;
            }
        }

        $this->command->info("✅ Berhasil membuat {$totalCreated} pesanan dengan detail produk.");
    }
}
