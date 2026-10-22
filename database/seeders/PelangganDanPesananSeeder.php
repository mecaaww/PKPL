<?php

namespace Database\Seeders;

use App\Models\DetailPesanan;
use App\Models\Obat;
use App\Models\Pesanan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PelangganDanPesananSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            ['Budi Santoso', 'budi.santoso@gmail.com'],
            ['Siti Rahmawati', 'siti.rahma@gmail.com'],
            ['Andi Pratama', 'andi.pratama@gmail.com'],
            ['Dewi Lestari', 'dewi.lestari@gmail.com'],
            ['Rizky Ramadhan', 'rizky.ramadhan@gmail.com'],
            ['Aulia Putri', 'aulia.putri@gmail.com'],
            ['Dimas Anggara', 'dimas.anggara@gmail.com'],
            ['Fitri Handayani', 'fitri.handayani@gmail.com'],
            ['Hendra Wijaya', 'hendra.wijaya@gmail.com'],
            ['Indah Permatasari', 'indah.permata@gmail.com'],
            ['Joko Susilo', 'joko.susilo@gmail.com'],
            ['Kartika Sari', 'kartika.sari@gmail.com'],
            ['Lukman Hakim', 'lukman.hakim@gmail.com'],
            ['Maya Safitri', 'maya.safitri@gmail.com'],
            ['Nugroho Adiputra', 'nugroho.adi@gmail.com'],
            ['Oki Setiawan', 'oki.setiawan@gmail.com'],
            ['Pratiwi Kusuma', 'pratiwi.kusuma@gmail.com'],
            ['Rendra Pratama', 'rendra.pratama@gmail.com'],
            ['Sari Indrawati', 'sari.indrawati@gmail.com'],
            ['Taufik Hidayat', 'taufik.hidayat@gmail.com'],
            ['Utami Wulandari', 'utami.wulan@gmail.com'],
            ['Vina Panduwinata', 'vina.pandu@gmail.com'],
            ['Wahyu Prasetyo', 'wahyu.prasetyo@gmail.com'],
            ['Yuni Shara', 'yuni.shara@gmail.com'],
            ['Zainal Abidin', 'zainal.abidin@gmail.com'],
            ['Agus Priyono', 'agus.priyono@gmail.com'],
            ['Bambang Pamungkas', 'bambang.p@gmail.com'],
            ['Cahya Kamila', 'cahya.kamila@gmail.com'],
            ['Dedi Mulyadi', 'dedi.mulyadi@gmail.com'],
            ['Eka Kurniawan', 'eka.kurniawan@gmail.com'],
            ['Fajar Nugraha', 'fajar.nugraha@gmail.com'],
            ['Gita Gutawa', 'gita.gutawa@gmail.com'],
            ['Hadi Pranoto', 'hadi.pranoto@gmail.com'],
            ['Irfan Bachdim', 'irfan.bachdim@gmail.com'],
            ['Julia Perez', 'julia.perez@gmail.com'],
            ['Kiki Amalia', 'kiki.amalia@gmail.com'],
            ['Lina Marlina', 'lina.marlina@gmail.com'],
            ['Muhammad Ridwan', 'm.ridwan@gmail.com'],
            ['Nadia Vega', 'nadia.vega@gmail.com'],
            ['Onky Alexander', 'onky.alex@gmail.com'],
            ['Putri Titian', 'putri.titian@gmail.com'],
            ['Raffi Ahmad', 'raffi.ahmad@gmail.com'],
            ['Sherina Munaf', 'sherina.munaf@gmail.com'],
            ['Tommy Kurniawan', 'tommy.k@gmail.com'],
            ['Uya Kuya', 'uya.kuya@gmail.com'],
            ['Vicky Shu', 'vicky.shu@gmail.com'],
            ['Wulan Guritno', 'wulan.guritno@gmail.com'],
            ['Yayan Ruhian', 'yayan.ruhian@gmail.com'],
            ['Zaskia Sungkar', 'zaskia.s@gmail.com'],
        ];

        $createdUsers = [];
        $totalCreatedUsers = 0;

        foreach ($names as $idx => [$name, $email]) {
            // Sebar pendaftaran pelanggan dari 350 hari lalu sampai 1 hari lalu
            $daysAgo = (int) round(350 * (1 - ($idx / count($names)))) + rand(-5, 5);
            $daysAgo = max(0, min(360, $daysAgo));
            $userDate = Carbon::now()->subDays($daysAgo)->subHours(rand(1, 10));

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'username'   => $name,
                    'password'   => Hash::make('password123'),
                    'role'       => 'pelanggan',
                    'created_at' => $userDate,
                    'updated_at' => $userDate,
                ]
            );

            // Jika user baru atau belum punya tanggal lama, update created_at nya
            if ($user->wasRecentlyCreated) {
                $totalCreatedUsers++;
            }
            $createdUsers[] = $user;
        }

        $allPelanggan = User::where('role', 'pelanggan')->get();

        // Ambil produk yang stoknya > 0
        $products = Obat::where('stok', '>', 0)->get();
        if ($products->isEmpty()) {
            $this->command->error('Tidak ada produk obat tersedia.');
            return;
        }

        $statusOptions = ['selesai', 'selesai', 'selesai', 'selesai', 'selesai', 'dikirim', 'dikemas'];
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
            'Jl. Danau Toba No. 77, Kota Malang',
            'Jl. Bendungan Sigura-gura No. 19, Kota Malang',
        ];

        // Buat pesanan tambahan di rentang 30 s/d 360 hari lalu agar setahun terakhir punya data kaya
        $additionalOrders = 0;
        for ($month = 11; $month >= 1; $month--) {
            // 8-15 pesanan per bulan lampau
            $orderCount = rand(8, 15);
            for ($o = 0; $o < $orderCount; $o++) {
                $daysAgo = ($month * 30) + rand(-10, 10);
                $daysAgo = max(31, min(355, $daysAgo));
                $orderDate = Carbon::now()->subDays($daysAgo)->setHour(rand(8, 20))->setMinute(rand(0, 59));

                // Pilih pelanggan yang sudah terdaftar sebelum atau pada orderDate
                $eligibleUsers = $allPelanggan->filter(fn($u) => Carbon::parse($u->created_at)->lte($orderDate));
                if ($eligibleUsers->isEmpty()) {
                    $eligibleUsers = $allPelanggan;
                }
                $user = $eligibleUsers->random();

                $status = $statusOptions[array_rand($statusOptions)];
                $metode = $metodePembayaran[array_rand($metodePembayaran)];
                $alamat = $alamatList[array_rand($alamatList)];
                $ongkir = [0, 5000, 8000, 10000, 12000, 15000][array_rand([0, 5000, 8000, 10000, 12000, 15000])];

                $invoiceNumber = 'INV-' . $orderDate->format('Ymd') . '-' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);

                $pesanan = Pesanan::create([
                    'nomor_invoice'      => $invoiceNumber,
                    'user_id'            => $user->id,
                    'latitude'           => -7.9 + (rand(-100, 100) / 10000),
                    'longitude'          => 112.6 + (rand(-100, 100) / 10000),
                    'alamat_lengkap'     => $alamat,
                    'detail_alamat'      => 'RT ' . rand(1, 15) . '/RW ' . rand(1, 10),
                    'ongkir'             => $ongkir,
                    'total_harga'        => 0,
                    'status_pesanan'     => $status,
                    'metode_pembayaran'  => $metode,
                    'created_at'         => $orderDate,
                    'updated_at'         => $orderDate,
                ]);

                // 1-4 items per order
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
                        'pesanan_id'           => $pesanan->id,
                        'obat_id'              => $product->id,
                        'jumlah'               => $jumlah,
                        'harga_satuan_asli'    => $hargaAsli,
                        'persentase_diskon'    => $diskon,
                        'harga_setelah_diskon' => $hargaDiskon,
                        'subtotal'             => $subtotal,
                        'created_at'           => $orderDate,
                        'updated_at'           => $orderDate,
                    ]);

                    $totalHarga += $subtotal;
                }

                $pesanan->update(['total_harga' => $totalHarga + $ongkir]);
                $additionalOrders++;
            }
        }

        // Distribusikan juga pesanan 30 hari terakhir agar pelanggan tidak cuma 5
        $recentOrders = Pesanan::where('created_at', '>=', Carbon::now()->subDays(30))->get();
        foreach ($recentOrders as $ro) {
            $eligibleUsers = $allPelanggan->filter(fn($u) => Carbon::parse($u->created_at)->lte($ro->created_at));
            if ($eligibleUsers->isNotEmpty()) {
                $ro->update(['user_id' => $eligibleUsers->random()->id]);
            }
        }

        $this->command->info("✅ Berhasil menambahkan {$totalCreatedUsers} pelanggan dan {$additionalOrders} pesanan sepanjang tahun!");
    }
}
