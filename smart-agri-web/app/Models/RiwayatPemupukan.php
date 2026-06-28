<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatPemupukan extends Model
{
    use HasFactory;

    protected $table = 'riwayat_pemupukans';

    protected $fillable = [
        'lahan_id',
        'tanggal_kalkulasi',
        'suhu',
        'kelembaban',
        'curah_hujan',
        'rekomendasi_n',
        'rekomendasi_p',
        'rekomendasi_k',
        'estimasi_panen',
        'estimasi_biaya',
        'ai_log'
    ];

    // Beritahu Laravel bahwa ai_log adalah array JSON
    protected $casts = [
        'ai_log' => 'array',
    ];

    public function lahan()
    {
        return $this->belongsTo(Lahan::class);
    }
}