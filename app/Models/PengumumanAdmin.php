<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengumumanAdmin extends Model
{
    use HasFactory;

    protected $table = 'pengumuman_admin';

    public const MIN_LENGTH = 20;

    public const MAX_LENGTH = 300;

    protected $fillable = [
        'konten',
        'user_id',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function current(): ?self
    {
        return self::latest()->first();
    }
}
