from fastapi import FastAPI
from pydantic import BaseModel
import pandas as pd
import numpy as np
from sklearn.neighbors import KNeighborsRegressor
import pyswarms as ps
import warnings

warnings.filterwarnings("ignore", category=UserWarning)

app = FastAPI(title="Smart Agriculture AI API")

try:
    df = pd.read_csv("dataset_knn_pso_ready.csv")
    X = df[['N_ratio', 'P_ratio', 'K_ratio', 'Soil_pH', 'Temperature_C', 'Humidity_pct', 'Rainfall_mm']]
    y = df['Yield_ton_per_ha']
    
    knn_model = KNeighborsRegressor(n_neighbors=3)
    knn_model.fit(X, y)
    model_status = "Model Hybrid KNN-PSO Berhasil Disiapkan dengan Dataset Baru!"
except Exception as e:
    knn_model = None
    model_status = f"Gagal memuat model: {str(e)}"

class DataLahan(BaseModel):
    n: float
    p: float
    k: float
    ph: float
    suhu: float
    kelembaban: float
    curah_hujan: float

@app.get("/")
def cek_status():
    return {"status": "aktif", "pesan": model_status}

@app.post("/hitung-optimasi")
def hitung_optimasi(data: DataLahan):
    if knn_model is None:
        return {"status": "error", "pesan": "Model AI belum siap."}

    def fungsi_evaluasi(particles):
        n_particles = particles.shape[0]
        biaya = np.zeros(n_particles)

        for i in range(n_particles):
            n_tambah, p_tambah, k_tambah = particles[i][0], particles[i][1], particles[i][2]

            input_simulasi = [[
                data.n + n_tambah, 
                data.p + p_tambah, 
                data.k + k_tambah,
                data.ph, 
                data.suhu, 
                data.kelembaban, 
                data.curah_hujan
            ]]

            prediksi_panen = knn_model.predict(input_simulasi)[0]
            skor_akhir = -prediksi_panen + (0.01 * (n_tambah + p_tambah + k_tambah))
            biaya[i] = skor_akhir

        return biaya

    batas_bawah = np.zeros(3)
    batas_atas = np.ones(3) * 50
    bounds = (batas_bawah, batas_atas)
    options = {'c1': 0.5, 'c2': 0.3, 'w': 0.9}

    optimizer = ps.single.GlobalBestPSO(n_particles=15, dimensions=3, options=options, bounds=bounds)
    cost, pos = optimizer.optimize(fungsi_evaluasi, iters=30, verbose=False)

    rekomendasi_n, rekomendasi_p, rekomendasi_k = pos[0], pos[1], pos[2]

    # MENGAMBIL RIWAYAT ITERASI PSO
    # Karena cost PSO bernilai negatif (karena kita mencari nilai max panen), 
    # kita kalikan -1 agar grafiknya naik sesuai logika peningkatan panen.
    riwayat_panen = [-c for c in optimizer.cost_history]

    estimasi_final = knn_model.predict([[
        data.n + rekomendasi_n, data.p + rekomendasi_p, data.k + rekomendasi_k,
        data.ph, data.suhu, data.kelembaban, data.curah_hujan
    ]])[0]

    return {
        "status": "berhasil",
        "pesan": "Kalkulasi Hybrid KNN-PSO selesai dijalankan",
        "estimasi_panen_ton_per_ha": round(estimasi_final, 2),
        "rekomendasi_pupuk_kg": {
            "N_tambahan": round(rekomendasi_n, 2),
            "P_tambahan": round(rekomendasi_p, 2),
            "K_tambahan": round(rekomendasi_k, 2)
        },
        "histori_iterasi": riwayat_panen # Data iterasi dikirim ke Laravel
    }