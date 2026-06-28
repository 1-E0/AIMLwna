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
            $table->float('suhu'); 
            $table->float('kelembaban');
            $table->float('curah_hujan');
            $table->float('rekomendasi_n'); 
            $table->float('rekomendasi_p'); 
            $table->float('rekomendasi_k');
            $table->float('estimasi_panen'); 
            $table->float('estimasi_biaya'); 
            $table->json('ai_log')->nullable(); // Menampung hasil visualisasi AI
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