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
        Schema::create('lahans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('kode_petak'); // Contoh: PLOT-A1
            $table->string('jenis_tanaman'); // Contoh: Jagung, Padi
            $table->float('luas_lahan'); // Dalam Hektar
            $table->float('toleransi_ph');
            $table->float('n_aktual')->default(0); // N saat ini
            $table->float('p_aktual')->default(0); // P saat ini
            $table->float('k_aktual')->default(0); // K saat ini
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lahans');
    }
};