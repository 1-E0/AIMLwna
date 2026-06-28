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

    <!-- Navbar -->
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

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Panel Kiri: Input Ekosistem & Cuaca -->
            <div class="col-span-1">
                <!-- Profil Lahan -->
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

                <!-- Form Input Makro Iklim -->
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
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
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
                            <span class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-yellow-300">Status: {{ $hasilTerbaru->status }}</span>
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

                        <!-- Canvas untuk Chart.js -->
                        <div class="w-full h-64 mb-4">
                            <canvas id="psoChart"></canvas>
                        </div>

                        @if($hasilTerbaru->status == 'Ditinjau')
                            <form action="{{ route('optimasi.terapkan', $hasilTerbaru->id) }}" method="POST">
                                @csrf
                                <button type="submit" onclick="return confirm('Apakah tim di lapangan sudah menerapkan pupuk ini ke tanah?')" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded shadow transition">
                                    Terapkan & Simpan Log ke Lahan
                                </button>
                            </form>
                        @endif
                    </div>
                    
                    <!-- Script Eksekusi Chart.js -->
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
                        <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <p class="text-lg">Belum ada data kalkulasi.</p>
                        <p class="text-sm mt-1">Masukkan data cuaca di samping dan klik Hitung Dosis Optimal.</p>
                    </div>
                @endif
            </div>

            <!-- Panel Bawah: Riwayat Tabel -->
            <div class="col-span-1 md:col-span-3 bg-white p-6 rounded-lg shadow mt-2">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold text-gray-800">Riwayat Pemupukan & Eksekusi</h2>
                    <!-- Tombol Export CSV -->
                    <a href="{{ route('optimasi.export', $lahan->id) }}" class="bg-gray-800 hover:bg-gray-900 text-white text-sm font-bold py-2 px-4 rounded inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
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
                                <th class="py-3 px-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($riwayats as $hist)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="py-3 px-4">{{ \Carbon\Carbon::parse($hist->tanggal_kalkulasi)->format('d M Y') }}</td>
                                    <td class="py-3 px-4">{{ $hist->suhu }}°C / {{ $hist->kelembaban }}% / {{ $hist->curah_hujan }}mm</td>
                                    <td class="py-3 px-4 font-mono text-blue-600">N:{{ $hist->rekomendasi_n }} P:{{ $hist->rekomendasi_p }} K:{{ $hist->rekomendasi_k }}</td>
                                    <td class="py-3 px-4 font-bold">{{ $hist->estimasi_panen }} Ton</td>
                                    <td class="py-3 px-4">
                                        @if($hist->status == 'Diterapkan')
                                            <span class="text-green-600 font-bold">&#10003; Diterapkan</span>
                                        @else
                                            <span class="text-yellow-600 font-bold">Ditinjau</span>
                                        @endif
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

    <!-- Footer Proyek -->
    <footer class="text-center mt-10 pb-6 text-sm text-gray-400">
        <p class="font-bold text-gray-500">Proyek Akhir Smart Agriculture &copy; 2026</p>
        <p>Tim Pengembang: Mario Joshua, Edbert Nathaniel Christian Yapar, Daniel Alvino, Aldwin</p>
    </footer>

</body>
</html>