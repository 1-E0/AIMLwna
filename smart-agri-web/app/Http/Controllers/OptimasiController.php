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
        
        $riwayats = RiwayatPemupukan::where('lahan_id', $lahan_id)
                        ->orderBy('created_at', 'desc')
                        ->get();
                        
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
            $response = Http::timeout(10)->post('http://127.0.0.1:8000/predict', [
                'n_aktual' => $lahan->n_aktual,
                'p_aktual' => $lahan->p_aktual,
                'k_aktual' => $lahan->k_aktual,
                'toleransi_ph' => $lahan->toleransi_ph,
                'suhu' => $request->suhu,
                'kelembaban' => $request->kelembaban,
                'curah_hujan' => $request->curah_hujan,
            ]);

            if ($response->successful()) {
                $data = $response->json();
            } else {
                throw new \Exception("Gagal menyambung ke AI");
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Pastikan Python FastAPI menyala. Error: ' . $e->getMessage());
        }

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
            'ai_log' => $data['ai_log'] // Simpan log detail dari Python
        ]);

        return redirect()->route('optimasi.index', $lahan->id)->with('success', 'Kalkulasi AI Selesai!');
    }

    public function detail($riwayat_id)
    {
        $riwayat = RiwayatPemupukan::with('lahan')->findOrFail($riwayat_id);
        return view('detail', compact('riwayat'));
    }

    public function exportCsv($lahan_id)
    {
        $lahan = Lahan::findOrFail($lahan_id);
        $riwayats = RiwayatPemupukan::where('lahan_id', $lahan_id)
                        ->orderBy('tanggal_kalkulasi', 'desc')
                        ->get();

        $fileName = "Laporan_AI_" . $lahan->kode_petak . ".csv";

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('Tanggal', 'Suhu (C)', 'Kelembaban (%)', 'Curah Hujan (mm)', 'Rekomendasi N (kg)', 'Rekomendasi P (kg)', 'Rekomendasi K (kg)', 'Estimasi Panen (Ton)');

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
                    $riwayat->estimasi_panen
                ));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}