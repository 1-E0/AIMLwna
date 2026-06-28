<?php

namespace App\Http\Controllers;

use App\Models\Lahan;
use App\Models\User;
use Illuminate\Http\Request;

class LahanController extends Controller
{
    public function index()
    {
        // Ambil semua data lahan dari database
        $lahans = Lahan::all();
        
        return view('lahan.index', compact('lahans'));
    }

    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'kode_petak' => 'required|string|max:255',
            'jenis_tanaman' => 'required|string|max:255',
            'luas_lahan' => 'required|numeric',
            'toleransi_ph' => 'required|numeric',
            'n_aktual' => 'required|numeric',
            'p_aktual' => 'required|numeric',
            'k_aktual' => 'required|numeric',
        ]);

        // Karena tidak ada login, kita buat user dummy secara otomatis jika belum ada
        $user = User::firstOrCreate(
            ['email' => 'admin@smartagri.com'],
            ['name' => 'Manajer Kebun', 'password' => bcrypt('rahasia')]
        );

        // Simpan data lahan ke database
        Lahan::create([
            'user_id' => $user->id,
            'kode_petak' => $request->kode_petak,
            'jenis_tanaman' => $request->jenis_tanaman,
            'luas_lahan' => $request->luas_lahan,
            'toleransi_ph' => $request->toleransi_ph,
            'n_aktual' => $request->n_aktual,
            'p_aktual' => $request->p_aktual,
            'k_aktual' => $request->k_aktual,
        ]);

        return redirect()->route('lahan.index')->with('success', 'Data lahan berhasil ditambahkan!');
    }

    public function destroy($id)
    {
        // Hapus lahan berdasarkan ID
        $lahan = Lahan::findOrFail($id);
        $lahan->delete();

        return redirect()->route('lahan.index')->with('success', 'Data lahan berhasil dihapus!');
    }
}