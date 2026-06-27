<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Agriculture - Optimasi AI</title>
    <!-- Memuat Chart.js dari CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f4f7f6; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .grid-form { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { display: flex; flex-direction: column; }
        input { padding: 8px; margin-top: 5px; box-sizing: border-box; }
        button { background-color: #28a745; color: white; padding: 12px 15px; border: none; border-radius: 4px; cursor: pointer; width: 100%; margin-top: 15px; font-weight: bold; }
        button:hover { background-color: #218838; }
        .result-box { background-color: #e9ecef; padding: 15px; margin-top: 20px; border-radius: 5px; border-left: 5px solid #007bff; }
        .chart-container { margin-top: 20px; background: white; padding: 15px; border-radius: 5px; border: 1px solid #ddd; }
    </style>
</head>
<body>

<div class="container">
    <h2>Showcase AI: Hybrid KNN & PSO Smart Agriculture</h2>
    <p>Masukkan parameter sensor tanah saat ini:</p>

    <form action="/optimasi" method="POST">
        @csrf
        <div class="grid-form">
            <div class="form-group">
                <label>N Ratio:</label>
                <input type="number" step="0.01" name="n" value="{{ $inputSebelumnya['n'] ?? 50 }}" required>
            </div>
            <div class="form-group">
                <label>Suhu (°C):</label>
                <input type="number" step="0.01" name="suhu" value="{{ $inputSebelumnya['suhu'] ?? 28 }}" required>
            </div>
            <div class="form-group">
                <label>P Ratio:</label>
                <input type="number" step="0.01" name="p" value="{{ $inputSebelumnya['p'] ?? 20 }}" required>
            </div>
            <div class="form-group">
                <label>Kelembaban (%):</label>
                <input type="number" step="0.01" name="kelembaban" value="{{ $inputSebelumnya['kelembaban'] ?? 75 }}" required>
            </div>
            <div class="form-group">
                <label>K Ratio:</label>
                <input type="number" step="0.01" name="k" value="{{ $inputSebelumnya['k'] ?? 20 }}" required>
            </div>
            <div class="form-group">
                <label>Curah Hujan (mm):</label>
                <input type="number" step="0.01" name="curah_hujan" value="{{ $inputSebelumnya['curah_hujan'] ?? 1500 }}" required>
            </div>
            <div class="form-group">
                <label>pH Tanah:</label>
                <input type="number" step="0.01" name="ph" value="{{ $inputSebelumnya['ph'] ?? 6.5 }}" required>
            </div>
        </div>
        <button type="submit">Jalankan 30 Iterasi PSO</button>
    </form>

    @if(isset($hasilAi))
        <div class="result-box">
            <h3>Hasil Keputusan AI:</h3>
            @if($hasilAi['status'] == 'berhasil')
                <p><strong>Target Maksimal Estimasi Panen:</strong> {{ $hasilAi['estimasi_panen_ton_per_ha'] }} Ton/Hektar</p>
                <p><strong>Koordinat Dosis Pupuk Optimal (Hasil Partikel Terbaik):</strong></p>
                <ul>
                    <li>Nitrogen (N): <b>{{ $hasilAi['rekomendasi_pupuk_kg']['N_tambahan'] }}</b> kg</li>
                    <li>Fosfor (P): <b>{{ $hasilAi['rekomendasi_pupuk_kg']['P_tambahan'] }}</b> kg</li>
                    <li>Kalium (K): <b>{{ $hasilAi['rekomendasi_pupuk_kg']['K_tambahan'] }}</b> kg</li>
                </ul>

                <!-- Tempat Kanvas Grafik Chart.js -->
                <div class="chart-container">
                    <h4>Visualisasi Pencarian GlobalBestPSO</h4>
                    <canvas id="psoChart"></canvas>
                </div>
            @else
                <p style="color: red;">Error: {{ $hasilAi['pesan'] }}</p>
            @endif
        </div>
    @endif
</div>

<!-- Logika JavaScript untuk merender Grafik -->
@if(isset($hasilAi) && $hasilAi['status'] == 'berhasil' && isset($hasilAi['histori_iterasi']))
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('psoChart').getContext('2d');
        
        // Mengambil data array iterasi dari PHP ke format JavaScript
        const dataIterasi = @json($hasilAi['histori_iterasi']);
        
        // Membuat label sumbu X (Iterasi 1, Iterasi 2, dst)
        const labels = dataIterasi.map((_, index) => 'Iterasi ' + (index + 1));
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Perkembangan Fitness (Estimasi Panen)',
                    data: dataIterasi,
                    borderColor: 'rgba(0, 123, 255, 1)',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                    pointRadius: 4,
                    tension: 0.3, // Membuat kurva melengkung halus
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        title: { display: true, text: 'Nilai Fitness (Panen)' }
                    },
                    x: {
                        title: { display: true, text: 'Jumlah Iterasi Kawanan' }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' Panen: ' + context.parsed.y.toFixed(2) + ' Ton';
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endif

</body>
</html>