<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LahanController;
use App\Http\Controllers\OptimasiController;

// Rute untuk Dashboard Utama Lahan
Route::get('/', [LahanController::class, 'index'])->name('lahan.index');
Route::post('/lahan', [LahanController::class, 'store'])->name('lahan.store');
Route::delete('/lahan/{id}', [LahanController::class, 'destroy'])->name('lahan.destroy');

// Rute untuk Halaman Optimasi (AI Simulator)
Route::get('/optimasi/{lahan_id}', [OptimasiController::class, 'index'])->name('optimasi.index');
Route::post('/optimasi/{lahan_id}/kalkulasi', [OptimasiController::class, 'kalkulasi'])->name('optimasi.kalkulasi');
Route::post('/optimasi/terapkan/{riwayat_id}', [OptimasiController::class, 'terapkan'])->name('optimasi.terapkan');

// Rute untuk Export Laporan CSV
Route::get('/optimasi/{lahan_id}/export', [OptimasiController::class, 'exportCsv'])->name('optimasi.export');