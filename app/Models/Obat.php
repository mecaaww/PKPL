<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Obat extends Model
{
    protected $table = 'obat';

    protected $fillable = [
        'nama',
        'kategori',
        'harga',
        'stok',
        'diskon_persen',
        'path_gambar'
    ];

    public $incrementing = false;
    protected $keyType = 'int';

    public function deskripsi()
    {
        return $this->hasMany(DeskripsiObat::class, 'obat_id', 'id')
                    ->orderBy('urutan');
    }

    public function getStatusDiskonAttribute(): array
    {
        $persen = (int) ($this->diskon_persen ?? 0);

        if ($persen === 0) {
            return [
                'status'       => 'Tanpa Diskon',
                'warna'        => 'Abu-abu',
                'persen'       => 0,
                'badge'        => 'Tanpa Diskon',
                'badge_full'   => '0% • Tanpa Diskon',
                'class'        => 'bg-gray-100 text-gray-700 border border-gray-300',
                'dot'          => 'bg-gray-400',
            ];
        }

        if ($persen >= 5 && $persen <= 10) {
            return [
                'status'       => 'Diskon Rendah',
                'warna'        => 'Hijau',
                'persen'       => $persen,
                'badge'        => $persen . '% • Diskon Rendah',
                'badge_full'   => $persen . '% • Diskon Rendah',
                'class'        => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                'dot'          => 'bg-emerald-500',
            ];
        }

        if ($persen >= 15 && $persen <= 20) {
            return [
                'status'       => 'Diskon Sedang',
                'warna'        => 'Kuning/oranye',
                'persen'       => $persen,
                'badge'        => $persen . '% • Diskon Sedang',
                'badge_full'   => $persen . '% • Diskon Sedang',
                'class'        => 'bg-amber-50 text-amber-700 border border-amber-200',
                'dot'          => 'bg-amber-500',
            ];
        }

        if ($persen >= 25 && $persen <= 50) {
            return [
                'status'       => 'Diskon Tinggi',
                'warna'        => 'Merah',
                'persen'       => $persen,
                'badge'        => $persen . '% • Diskon Tinggi',
                'badge_full'   => $persen . '% • Diskon Tinggi',
                'class'        => 'bg-rose-50 text-rose-700 border border-rose-200',
                'dot'          => 'bg-rose-500',
            ];
        }

        // Fallback rentang lainnya
        if ($persen < 5) {
            return [
                'status'       => 'Diskon Rendah',
                'warna'        => 'Hijau',
                'persen'       => $persen,
                'badge'        => $persen . '% • Diskon Rendah',
                'badge_full'   => $persen . '% • Diskon Rendah',
                'class'        => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                'dot'          => 'bg-emerald-500',
            ];
        } elseif ($persen < 15) {
            return [
                'status'       => 'Diskon Rendah',
                'warna'        => 'Hijau',
                'persen'       => $persen,
                'badge'        => $persen . '% • Diskon Rendah',
                'badge_full'   => $persen . '% • Diskon Rendah',
                'class'        => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                'dot'          => 'bg-emerald-500',
            ];
        } elseif ($persen < 25) {
            return [
                'status'       => 'Diskon Sedang',
                'warna'        => 'Kuning/oranye',
                'persen'       => $persen,
                'badge'        => $persen . '% • Diskon Sedang',
                'badge_full'   => $persen . '% • Diskon Sedang',
                'class'        => 'bg-amber-50 text-amber-700 border border-amber-200',
                'dot'          => 'bg-amber-500',
            ];
        } else {
            return [
                'status'       => 'Diskon Tinggi',
                'warna'        => 'Merah',
                'persen'       => $persen,
                'badge'        => $persen . '% • Diskon Tinggi',
                'badge_full'   => $persen . '% • Diskon Tinggi',
                'class'        => 'bg-rose-50 text-rose-700 border border-rose-200',
                'dot'          => 'bg-rose-500',
            ];
        }
    }
}
