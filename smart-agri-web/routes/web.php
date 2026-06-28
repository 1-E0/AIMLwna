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
Route::get('/optimasi/{lahan_id}/export', [OptimasiController::class, 'exportCsv'])->name('optimasi.export');

// Rute untuk Halaman Detail Visualisasi AI
Route::get('/optimasi/detail/{riwayat_id}', [OptimasiController::class, 'detail'])->name('optimasi.detail');