<?php

use App\Http\Controllers\OptimasiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/optimasi', [OptimasiController::class, 'index']);
Route::post('/optimasi', [OptimasiController::class, 'hitung']);
