<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('riwayat_pemupukans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lahan_id')->constrained('lahans')->onDelete('cascade');
            $table->date('tanggal_kalkulasi');
            $table->float('suhu'); // Data makro meteorologi
            $table->float('kelembaban');
            $table->float('curah_hujan');
            $table->float('rekomendasi_n'); // Hasil dari PSO
            $table->float('rekomendasi_p'); 
            $table->float('rekomendasi_k');
            $table->float('estimasi_panen'); // Proyeksi panen KNN
            $table->float('estimasi_biaya'); // Anggaran pupuk
            $table->enum('status', ['Ditinjau', 'Diterapkan'])->default('Ditinjau');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_pemupukans');
    }
};