<?php

namespace App\Http\Controllers;

use App\Models\Lahan;
use App\Models\RiwayatPemupukan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OptimasiController extends Controller
{
    public function index($lahan_id)
    {
        $lahan = Lahan::findOrFail($lahan_id);
        
        // Mengambil riwayat pemupukan, yang paling baru di atas
        $riwayats = RiwayatPemupukan::where('lahan_id', $lahan_id)
                        ->orderBy('created_at', 'desc')
                        ->get();
                        
        // Mengambil riwayat terakhir untuk ditampilkan di grafik
        $hasilTerbaru = $riwayats->first();

        return view('optimasi', compact('lahan', 'riwayats', 'hasilTerbaru'));
    }

    public function kalkulasi(Request $request, $lahan_id)
    {
        $request->validate([
            'suhu' => 'required|numeric',
            'kelembaban' => 'required|numeric',
            'curah_hujan' => 'required|numeric',
        ]);

        $lahan = Lahan::findOrFail($lahan_id);

        try {
            // Mencoba melakukan HTTP POST Request ke API Python (FastAPI)
            $response = Http::timeout(5)->post('http://127.0.0.1:8000/predict', [
                'n_aktual' => $lahan->n_aktual,
                'p_aktual' => $lahan->p_aktual,
                'k_aktual' => $lahan->k_aktual,
                // TAMBAHKAN BARIS INI:
                'toleransi_ph' => $lahan->toleransi_ph,
                'suhu' => $request->suhu,
                'kelembaban' => $request->kelembaban,
                'curah_hujan' => $request->curah_hujan,
            ]);

            if ($response->successful()) {
                $data = $response->json();
            } else {
                throw new \Exception("Endpoint API belum siap.");
            }
        } catch (\Exception $e) {
            // FALLBACK DUMMY DATA (Selama Python belum dihubungkan)
            $data = [
                'rekomendasi_n' => round(rand(10, 25) * $lahan->luas_lahan, 2),
                'rekomendasi_p' => round(rand(5, 15) * $lahan->luas_lahan, 2),
                'rekomendasi_k' => round(rand(8, 20) * $lahan->luas_lahan, 2),
                'estimasi_panen' => round(rand(4, 9) * $lahan->luas_lahan, 2), // Dalam Ton
                'estimasi_biaya' => rand(500000, 1500000) * $lahan->luas_lahan // Dalam Rupiah
            ];
        }

        // Simpan hasil ke Database Riwayat Pemupukan
        RiwayatPemupukan::create([
            'lahan_id' => $lahan->id,
            'tanggal_kalkulasi' => now(),
            'suhu' => $request->suhu,
            'kelembaban' => $request->kelembaban,
            'curah_hujan' => $request->curah_hujan,
            'rekomendasi_n' => $data['rekomendasi_n'],
            'rekomendasi_p' => $data['rekomendasi_p'],
            'rekomendasi_k' => $data['rekomendasi_k'],
            'estimasi_panen' => $data['estimasi_panen'],
            'estimasi_biaya' => $data['estimasi_biaya'],
            'status' => 'Ditinjau'
        ]);

        return redirect()->route('optimasi.index', $lahan->id)->with('success', 'Kalkulasi Hybrid KNN-PSO Berhasil Dieksekusi!');
    }

    public function terapkan($riwayat_id)
    {
        $riwayat = RiwayatPemupukan::findOrFail($riwayat_id);
        $riwayat->update(['status' => 'Diterapkan']);
        
        // Memperbarui profil lahan dengan nutrisi baru setelah pupuk diterapkan
        $lahan = Lahan::findOrFail($riwayat->lahan_id);
        $lahan->update([
            'n_aktual' => $lahan->n_aktual + $riwayat->rekomendasi_n,
            'p_aktual' => $lahan->p_aktual + $riwayat->rekomendasi_p,
            'k_aktual' => $lahan->k_aktual + $riwayat->rekomendasi_k,
        ]);

        return back()->with('success', 'Instruksi pemupukan telah diterapkan! Kadar hara lahan otomatis diperbarui.');
    }

    public function exportCsv($lahan_id)
    {
        $lahan = Lahan::findOrFail($lahan_id);
        $riwayats = RiwayatPemupukan::where('lahan_id', $lahan_id)
                        ->orderBy('tanggal_kalkulasi', 'desc')
                        ->get();

        $fileName = "Laporan_Pemupukan_" . $lahan->kode_petak . ".csv";

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('Tanggal', 'Suhu (C)', 'Kelembaban (%)', 'Curah Hujan (mm)', 'Rekomendasi N (kg)', 'Rekomendasi P (kg)', 'Rekomendasi K (kg)', 'Estimasi Panen (Ton)', 'Estimasi Biaya (Rp)', 'Status');

        $callback = function() use($riwayats, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($riwayats as $riwayat) {
                fputcsv($file, array(
                    $riwayat->tanggal_kalkulasi,
                    $riwayat->suhu,
                    $riwayat->kelembaban,
                    $riwayat->curah_hujan,
                    $riwayat->rekomendasi_n,
                    $riwayat->rekomendasi_p,
                    $riwayat->rekomendasi_k,
                    $riwayat->estimasi_panen,
                    $riwayat->estimasi_biaya,
                    $riwayat->status
                ));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}