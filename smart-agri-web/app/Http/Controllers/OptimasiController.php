<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OptimasiController extends Controller
{
    // Menampilkan halaman form
    public function index()
    {
        return view('optimasi');
    }

    // Mengirim data form ke API Python
    public function hitung(Request $request)
    {
        // 1. Ambil data inputan dari web
        $dataLahan = [
            'n' => (float) $request->input('n', 0),
            'p' => (float) $request->input('p', 0),
            'k' => (float) $request->input('k', 0),
            'ph' => (float) $request->input('ph', 0),
            'suhu' => (float) $request->input('suhu', 0),
            'kelembaban' => (float) $request->input('kelembaban', 0),
            'curah_hujan' => (float) $request->input('curah_hujan', 0),
        ];

        // 2. Tembak API Python FastAPI
        try {
            $response = Http::post('http://127.0.0.1:8000/hitung-optimasi', $dataLahan);
            $hasilAi = $response->json();
        } catch (\Exception $e) {
            $hasilAi = ['status' => 'error', 'pesan' => 'Gagal terhubung ke mesin AI Python. Pastikan Uvicorn menyala.'];
        }

        // 3. Kembalikan data AI ke halaman web
        return view('optimasi', ['hasilAi' => $hasilAi, 'inputSebelumnya' => $dataLahan]);
    }
}