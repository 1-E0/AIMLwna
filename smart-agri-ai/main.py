from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import pandas as pd
import numpy as np
from sklearn.neighbors import KNeighborsRegressor
import pyswarms as ps

app = FastAPI(title="Smart Agriculture AI Microservice")

# --- 1. MODEL DATA REQUEST DARI LARAVEL ---
class LahanData(BaseModel):
    n_aktual: float
    p_aktual: float
    k_aktual: float
    toleransi_ph: float  # Ditambahkan agar sesuai dengan Soil_pH di dataset
    suhu: float
    kelembaban: float
    curah_hujan: float

# --- 2. INISIALISASI DATASET & MODEL KNN ---
DATASET_FILE = "dataset_knn_pso_ready.csv"
knn_model = KNeighborsRegressor(n_neighbors=5)

def prepare_model():
    """Fungsi untuk melatih model KNN secara langsung dari dataset CSV asli"""
    global knn_model
    
    try:
        # Membaca dataset historis asli Anda
        df = pd.read_csv(DATASET_FILE)
        
        # Mengambil 7 fitur secara eksplisit berdasarkan nama kolom dataset
        X = df[['N_ratio', 'P_ratio', 'K_ratio', 'Soil_pH', 'Temperature_C', 'Humidity_pct', 'Rainfall_mm']].values
        
        # Target/Label
        y = df['Yield_ton_per_ha'].values
        
        # Melatih KNN untuk mencari pola
        knn_model.fit(X, y)
        print("=====================================================")
        print(f"SUCCESS: Model KNN dilatih dengan dataset '{DATASET_FILE}'")
        print(f"Total Fitur     : {X.shape[1]} Variabel")
        print(f"Total Baris Data: {len(df)} Baris")
        print("=====================================================")
        
    except FileNotFoundError:
        print(f"ERROR: File '{DATASET_FILE}' tidak ditemukan di folder smart-agri-ai.")
    except Exception as e:
        print(f"ERROR SAAT MEMBACA DATASET: {e}")

# Jalankan persiapan model saat API pertama kali dihidupkan
prepare_model()


# --- 3. FUNGSI FITNESS (OBJEKTIF) UNTUK PSO ---
def fitness_function(particles, n_aktual, p_aktual, k_aktual, toleransi_ph, suhu, kelembaban, curah_hujan):
    n_particles = particles.shape[0]
    costs = np.zeros(n_particles)
    
    # Asumsi harga pupuk per Kg (Rupiah)
    HARGA_N = 12000 
    HARGA_P = 15000
    HARGA_K = 14000
    HARGA_PANEN_PER_TON = 6000000 

    for i in range(n_particles):
        # Partikel i membawa kandidat tambahan dosis [N, P, K]
        delta_n, delta_p, delta_k = particles[i]
        
        # Kalkulasi biaya pembelian pupuk
        biaya_pupuk = (delta_n * HARGA_N) + (delta_p * HARGA_P) + (delta_k * HARGA_K)
        
        # Susunan matriks HARUS urut seperti dataset: 
        # [N, P, K, pH, Suhu, Kelembaban, Curah Hujan]
        input_features = np.array([[
            n_aktual + delta_n, 
            p_aktual + delta_p, 
            k_aktual + delta_k,
            toleransi_ph,
            suhu, 
            kelembaban, 
            curah_hujan
        ]])
        
        # Prediksi hasil panen dari KNN (Ton)
        prediksi_panen = knn_model.predict(input_features)[0]
        
        # Fungsi Objektif = Biaya Pupuk - Pendapatan Panen
        costs[i] = biaya_pupuk - (prediksi_panen * HARGA_PANEN_PER_TON)
        
    return costs


# --- 4. ENDPOINT API UTAMA ---
@app.get("/")
def read_root():
    return {"status": "aktif", "pesan": "Model Hybrid KNN-PSO Berhasil Disiapkan dengan Dataset Asli!"}

@app.post("/predict")
def predict_optimal_fertilizer(data: LahanData):
    try:
        # Konfigurasi Kawanan Partikel (Swarm Intelligence)
        options = {'c1': 1.5, 'c2': 1.5, 'w': 0.5} 
        
        # Batas min-max pencarian (0 - 50 kg)
        min_bounds = np.array([0.0, 0.0, 0.0])
        max_bounds = np.array([50.0, 50.0, 50.0])
        bounds = (min_bounds, max_bounds)
        
        # Lepaskan partikel virtual
        optimizer = ps.single.GlobalBestPSO(n_particles=30, dimensions=3, options=options, bounds=bounds)
        
        # Optimasi Dosis
        best_cost, best_pos = optimizer.optimize(
            fitness_function, 
            iters=50, 
            n_aktual=data.n_aktual, 
            p_aktual=data.p_aktual, 
            k_aktual=data.k_aktual,
            toleransi_ph=data.toleransi_ph,
            suhu=data.suhu, 
            kelembaban=data.kelembaban, 
            curah_hujan=data.curah_hujan,
            verbose=False 
        )
        
        # Hasil PSO
        rekomendasi_n = round(best_pos[0], 2)
        rekomendasi_p = round(best_pos[1], 2)
        rekomendasi_k = round(best_pos[2], 2)
        
        # Hitung ulang KNN dengan dosis final
        final_features = np.array([[
            data.n_aktual + rekomendasi_n, 
            data.p_aktual + rekomendasi_p, 
            data.k_aktual + rekomendasi_k,
            data.toleransi_ph,
            data.suhu, 
            data.kelembaban, 
            data.curah_hujan
        ]])
        estimasi_panen = round(knn_model.predict(final_features)[0], 2)
        estimasi_biaya = (rekomendasi_n * 12000) + (rekomendasi_p * 15000) + (rekomendasi_k * 14000)

        return {
            "rekomendasi_n": rekomendasi_n,
            "rekomendasi_p": rekomendasi_p,
            "rekomendasi_k": rekomendasi_k,
            "estimasi_panen": estimasi_panen,
            "estimasi_biaya": estimasi_biaya
        }

    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))