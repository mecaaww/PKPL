<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetOmzet extends Model
{
    use HasFactory;

    protected $table = 'target_omzet';

    public const MIN_TARGET = 1000000;

    public const MAX_TARGET = 30000000;

    protected $fillable = [
        'bulan',
        'tahun',
        'target',
    ];

    protected $casts = [
        'bulan' => 'integer',
        'tahun' => 'integer',
        'target' => 'integer',
    ];

    public static function forCurrentMonth(): ?self
    {
        return self::where('bulan', Carbon::now()->month)
            ->where('tahun', Carbon::now()->year)
            ->first();
    }

    public function getFormattedTargetAttribute(): string
    {
        return 'Rp'.number_format($this->target, 0, ',', '.');
    }
}
