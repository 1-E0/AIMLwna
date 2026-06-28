<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulasi Nutrisi AI - {{ $lahan->kode_petak }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 font-sans pb-10">

    <nav class="bg-blue-700 text-white p-4 shadow-md flex justify-between items-center">
        <div>
            <a href="{{ route('lahan.index') }}" class="text-blue-200 hover:text-white mr-4 text-sm font-bold">
                &larr; KEMBALI
            </a>
            <span class="text-xl font-bold">Modul Simulator KNN-PSO</span>
        </div>
        <span class="bg-blue-800 px-3 py-1 rounded text-sm shadow-inner">{{ $lahan->kode_petak }} - {{ $lahan->jenis_tanaman }}</span>
    </nav>

    <div class="container mx-auto p-6 mt-4">
        
        @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 shadow-sm" role="alert">
                <p class="font-bold">Berhasil</p>
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 shadow-sm" role="alert">
                <p class="font-bold">Gagal</p>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Panel Kiri: Input Ekosistem & Cuaca -->
            <div class="col-span-1">
                <div class="bg-white p-5 rounded-lg shadow border-t-4 border-blue-500 mb-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-2">Kondisi Lahan Saat Ini</h2>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li><strong>Luas:</strong> {{ $lahan->luas_lahan }} Hektar</li>
                        <li><strong>Tanaman:</strong> {{ $lahan->jenis_tanaman }}</li>
                        <li><strong>pH Tanah:</strong> {{ $lahan->toleransi_ph }}</li>
                    </ul>
                    <div class="mt-4 flex gap-2 justify-between text-center font-mono">
                        <div class="bg-gray-100 p-2 rounded flex-1">N<br><span class="text-lg font-bold text-blue-600">{{ $lahan->n_aktual }}</span></div>
                        <div class="bg-gray-100 p-2 rounded flex-1">P<br><span class="text-lg font-bold text-blue-600">{{ $lahan->p_aktual }}</span></div>
                        <div class="bg-gray-100 p-2 rounded flex-1">K<br><span class="text-lg font-bold text-blue-600">{{ $lahan->k_aktual }}</span></div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-lg shadow">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Data Makro Iklim</h2>
                    <form action="{{ route('optimasi.kalkulasi', $lahan->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Suhu Udara (°C)</label>
                            <input type="number" step="0.1" name="suhu" required class="mt-1 w-full border border-gray-300 rounded p-2 focus:border-blue-500 outline-none">
                        </div>
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Kelembaban (%)</label>
                            <input type="number" step="0.1" name="kelembaban" required class="mt-1 w-full border border-gray-300 rounded p-2 focus:border-blue-500 outline-none">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Curah Hujan Harian (mm)</label>
                            <input type="number" step="0.1" name="curah_hujan" required class="mt-1 w-full border border-gray-300 rounded p-2 focus:border-blue-500 outline-none">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded shadow-lg transition duration-200 flex justify-center items-center gap-2">
                            Hitung Dosis Optimal (PSO)
                        </button>
                    </form>
                </div>
            </div>

            <!-- Panel Kanan: Hasil Optimasi & Chart -->
            <div class="col-span-2">
                @if($hasilTerbaru)
                    <div class="bg-white p-6 rounded-lg shadow mb-6 border border-green-200">
                        <div class="flex justify-between items-start mb-4">
                            <h2 class="text-2xl font-bold text-gray-800">Rekomendasi Takaran Pupuk Presisi</h2>
                            <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-green-300">Kalkulasi AI Berhasil</span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="bg-blue-50 rounded-lg p-4 flex flex-col justify-center items-center border border-blue-100">
                                <span class="text-sm text-blue-800 font-semibold mb-1">Proyeksi Panen (KNN)</span>
                                <span class="text-3xl font-black text-blue-600">{{ $hasilTerbaru->estimasi_panen }} <span class="text-lg">Ton</span></span>
                            </div>
                            <div class="bg-red-50 rounded-lg p-4 flex flex-col justify-center items-center border border-red-100">
                                <span class="text-sm text-red-800 font-semibold mb-1">Estimasi Anggaran Pupuk</span>
                                <span class="text-2xl font-black text-red-600">Rp {{ number_format($hasilTerbaru->estimasi_biaya, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="w-full h-64 mb-4">
                            <canvas id="psoChart"></canvas>
                        </div>
                    </div>
                    
                    <script>
                        const ctx = document.getElementById('psoChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: ['Nitrogen (N)', 'Fosfor (P)', 'Kalium (K)'],
                                datasets: [{
                                    label: 'Dosis Tambahan yg Direkomendasikan (Kg)',
                                    data: [{{ $hasilTerbaru->rekomendasi_n }}, {{ $hasilTerbaru->rekomendasi_p }}, {{ $hasilTerbaru->rekomendasi_k }}],
                                    backgroundColor: [
                                        'rgba(54, 162, 235, 0.6)',
                                        'rgba(255, 99, 132, 0.6)',
                                        'rgba(255, 206, 86, 0.6)'
                                    ],
                                    borderColor: [
                                        'rgba(54, 162, 235, 1)',
                                        'rgba(255, 99, 132, 1)',
                                        'rgba(255, 206, 86, 1)'
                                    ],
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        title: { display: true, text: 'Berat (Kilogram)' }
                                    }
                                }
                            }
                        });
                    </script>
                @else
                    <div class="bg-white p-10 rounded-lg shadow flex flex-col items-center justify-center text-gray-400 h-full border-2 border-dashed border-gray-300">
                        <p class="text-lg">Belum ada data kalkulasi.</p>
                        <p class="text-sm mt-1">Masukkan data cuaca di samping dan klik Hitung Dosis Optimal.</p>
                    </div>
                @endif
            </div>

            <!-- Panel Bawah: Riwayat Tabel -->
            <div class="col-span-1 md:col-span-3 bg-white p-6 rounded-lg shadow mt-2">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold text-gray-800">Riwayat Pemupukan & Eksekusi</h2>
                    <a href="{{ route('optimasi.export', $lahan->id) }}" class="bg-gray-800 hover:bg-gray-900 text-white text-sm font-bold py-2 px-4 rounded inline-flex items-center gap-2">
                        Unduh Laporan CSV
                    </a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                            <tr>
                                <th class="py-3 px-4">Tanggal</th>
                                <th class="py-3 px-4">Iklim (S/K/C)</th>
                                <th class="py-3 px-4">Rek. NPK (Kg)</th>
                                <th class="py-3 px-4">Est. Panen</th>
                                <th class="py-3 px-4 text-center">Analisis AI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($riwayats as $hist)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="py-3 px-4">{{ \Carbon\Carbon::parse($hist->tanggal_kalkulasi)->format('d M Y') }}</td>
                                    <td class="py-3 px-4">{{ $hist->suhu }}°C / {{ $hist->kelembaban }}% / {{ $hist->curah_hujan }}mm</td>
                                    <td class="py-3 px-4 font-mono text-blue-600">N:{{ $hist->rekomendasi_n }} P:{{ $hist->rekomendasi_p }} K:{{ $hist->rekomendasi_k }}</td>
                                    <td class="py-3 px-4 font-bold">{{ $hist->estimasi_panen }} Ton</td>
                                    <td class="py-3 px-4 text-center">
                                        <a href="{{ route('optimasi.detail', $hist->id) }}" target="_blank" class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold py-2 px-3 rounded shadow inline-block">
                                            🔍 Lihat Detail Algoritma
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-4 text-center text-gray-400">Belum ada riwayat tercatat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</body>
</html>