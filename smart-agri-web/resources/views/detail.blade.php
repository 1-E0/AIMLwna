<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Detail Algoritma Hybrid KNN-PSO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 font-sans pb-10">

    <div class="bg-purple-800 text-white p-6 shadow-md">
        <div class="container mx-auto">
            <h1 class="text-3xl font-bold">Laporan Diagnostik Algoritma Machine Learning</h1>
            <p class="text-purple-200 mt-2">Menampilkan simulasi Particle Swarm Optimization (PSO) & Regresi K-Nearest Neighbors (KNN)</p>
        </div>
    </div>

    <div class="container mx-auto p-6 mt-4">
        
        <div class="bg-white p-6 rounded-lg shadow-md mb-6 border-l-4 border-purple-500">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Ringkasan Parameter Eksekusi</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div class="bg-gray-50 p-3 rounded">
                    <span class="block text-gray-500">ID Kalkulasi</span>
                    <strong class="text-lg">#{{ $riwayat->id }} ({{ \Carbon\Carbon::parse($riwayat->tanggal_kalkulasi)->format('d M Y') }})</strong>
                </div>
                <div class="bg-gray-50 p-3 rounded">
                    <span class="block text-gray-500">Kondisi Cuaca (Suhu/Hum/Rain)</span>
                    <strong class="text-lg">{{ $riwayat->suhu }}°C / {{ $riwayat->kelembaban }}% / {{ $riwayat->curah_hujan }}mm</strong>
                </div>
                <div class="bg-gray-50 p-3 rounded">
                    <span class="block text-gray-500">Total Dataset Historis (KNN)</span>
                    <strong class="text-lg">{{ $riwayat->ai_log['dataset_rows'] ?? 'N/A' }} Baris Data</strong>
                </div>
                <div class="bg-gray-50 p-3 rounded">
                    <span class="block text-gray-500">Output Final PSO (Tambahan N-P-K)</span>
                    <strong class="text-lg text-blue-600 font-mono">{{ $riwayat->rekomendasi_n }} | {{ $riwayat->rekomendasi_p }} | {{ $riwayat->rekomendasi_k }} kg</strong>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-bold text-gray-800 mb-2">1. Kurva Konvergensi PSO</h2>
                <p class="text-sm text-gray-600 mb-4">Grafik ini membuktikan kawanan partikel bergerak mencari titik minimum (fungsi fitness). Semakin turun kurvanya, semakin besar keuntungan yang diraih.</p>
                <div class="w-full h-72">
                    <canvas id="psoConvergenceChart"></canvas>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-bold text-gray-800 mb-2">2. Analisis Regresi K-Nearest Neighbors (K=5)</h2>
                <p class="text-sm text-gray-600 mb-4">Sistem mencari 5 data historis yang memiliki kemiripan iklim & unsur hara paling dekat (Euclidean Distance) untuk memprediksi panen.</p>
                
                <table class="min-w-full text-sm text-left mt-4 border">
                    <thead class="bg-purple-100 text-purple-900">
                        <tr>
                            <th class="py-2 px-3 border-b">Tetangga Terdekat</th>
                            <th class="py-2 px-3 border-b">Jarak Euclidean</th>
                            <th class="py-2 px-3 border-b">Panen Historis (Ton)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($riwayat->ai_log['knn_neighbors']))
                            @foreach($riwayat->ai_log['knn_neighbors'] as $knn)
                            <tr class="border-b">
                                <td class="py-2 px-3">Titik #{{ $knn['tetangga_ke'] }}</td>
                                <td class="py-2 px-3 font-mono">{{ $knn['jarak_euclidean'] }}</td>
                                <td class="py-2 px-3 font-bold">{{ $knn['yield_historis'] }} Ton</td>
                            </tr>
                            @endforeach
                            <tr class="bg-gray-100 font-bold text-lg">
                                <td colspan="2" class="py-3 px-3 text-right">Prediksi Panen Final (Rata-rata K=5):</td>
                                <td class="py-3 px-3 text-blue-700">{{ $riwayat->estimasi_panen }} Ton</td>
                            </tr>
                        @else
                            <tr><td colspan="3" class="text-center text-gray-400 py-4">Data KNN tidak direkam di versi sebelumnya.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <script>
        const costHistory = @json($riwayat->ai_log['pso_cost_history'] ?? []);
        const labels = Array.from({length: costHistory.length}, (_, i) => 'Iterasi ' + (i + 1));

        const ctx = document.getElementById('psoConvergenceChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Nilai Cost (Biaya - Pendapatan)',
                    data: costHistory,
                    borderColor: 'rgba(147, 51, 234, 1)', 
                    backgroundColor: 'rgba(147, 51, 234, 0.2)',
                    borderWidth: 2,
                    pointRadius: 1,
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { title: { display: true, text: 'Iterasi' } },
                    y: { title: { display: true, text: 'Skor Fitness' } }
                },
                plugins: {
                    tooltip: { mode: 'index', intersect: false }
                }
            }
        });
    </script>
</body>
</html>