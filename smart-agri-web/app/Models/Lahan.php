<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lahan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'kode_petak',
        'jenis_tanaman',
        'luas_lahan',
        'toleransi_ph',
        'n_aktual',
        'p_aktual',
        'k_aktual',
    ];

    // Relasi: Satu lahan dimiliki oleh satu manajer (user)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: Satu lahan memiliki banyak riwayat pemupukan
    public function riwayatPemupukan()
    {
        return $this->hasMany(RiwayatPemupukan::class);
    }
}