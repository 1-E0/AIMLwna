from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import pandas as pd
import numpy as np
from sklearn.neighbors import KNeighborsRegressor
from sklearn.preprocessing import StandardScaler # [PERBAIKAN 1] Import StandardScaler
import pyswarms as ps

app = FastAPI(title="Smart Agriculture AI Microservice")

class LahanData(BaseModel):
    n_aktual: float
    p_aktual: float
    k_aktual: float
    toleransi_ph: float
    suhu: float
    kelembaban: float
    curah_hujan: float

DATASET_FILE = "dataset_knn_pso_ready.csv"
knn_model = KNeighborsRegressor(n_neighbors=5)
scaler = StandardScaler() # [PERBAIKAN 1] Inisialisasi Scaler global

# Simpan dataset ke memory global agar bisa diakses untuk visualisasi detail
X_train_global = None
y_train_global = None

def prepare_model():
    global knn_model, scaler, X_train_global, y_train_global
    try:
        df = pd.read_csv(DATASET_FILE)
        X_train_global = df[['N_ratio', 'P_ratio', 'K_ratio', 'Soil_pH', 'Temperature_C', 'Humidity_pct', 'Rainfall_mm']].values
        y_train_global = df['Yield_ton_per_ha'].values
        
        # [PERBAIKAN 1] Fit dan Transform data fitur sebelum masuk KNN
        X_train_global_scaled = scaler.fit_transform(X_train_global)
        
        # Latih model menggunakan data yang sudah diskala
        knn_model.fit(X_train_global_scaled, y_train_global)
        
        print("=====================================================")
        print(f"SUCCESS: Model KNN dilatih dengan dataset '{DATASET_FILE}'")
        print(f"Total Fitur     : {X_train_global.shape[1]} Variabel (Scaled)")
        print(f"Total Baris Data: {len(df)} Baris")
        print("=====================================================")
    except FileNotFoundError:
        print(f"ERROR: File '{DATASET_FILE}' tidak ditemukan di folder smart-agri-ai.")
    except Exception as e:
        print(f"ERROR SAAT MEMBACA DATASET: {e}")

prepare_model()

def fitness_function(particles, n_aktual, p_aktual, k_aktual, toleransi_ph, suhu, kelembaban, curah_hujan):
    n_particles = particles.shape[0]
    
    HARGA_N = 12000; HARGA_P = 15000; HARGA_K = 14000; HARGA_PANEN_PER_TON = 6000000 
    
    # [PERBAIKAN 2] Vectorization (menghilangkan for-loop agar AI berlari lebih cepat)
    delta_n = particles[:, 0]
    delta_p = particles[:, 1]
    delta_k = particles[:, 2]
    
    biaya_pupuk = (delta_n * HARGA_N) + (delta_p * HARGA_P) + (delta_k * HARGA_K)
    
    # Buat matriks fitur untuk seluruh partikel sekaligus
    input_features = np.zeros((n_particles, 7))
    input_features[:, 0] = n_aktual + delta_n
    input_features[:, 1] = p_aktual + delta_p
    input_features[:, 2] = k_aktual + delta_k
    input_features[:, 3] = toleransi_ph
    input_features[:, 4] = suhu
    input_features[:, 5] = kelembaban
    input_features[:, 6] = curah_hujan
    
    # [PERBAIKAN 1] Skala input fitur sebelum diprediksi
    input_features_scaled = scaler.transform(input_features)
    prediksi_panen = knn_model.predict(input_features_scaled)
    
    # [PERBAIKAN 3] Penalti Agronomi (Mencegah Over-fertilization)
    # Jika sistem menambahkan pupuk terlalu ekstrem, kita berikan "biaya kerugian tak terlihat"
    # agar kurva konvergensi PSO mencari titik penambahan pupuk yang rasional.
    penalti_toksisitas = (delta_n + delta_p + delta_k) * 30000 
    
    costs = biaya_pupuk - (prediksi_panen * HARGA_PANEN_PER_TON) + penalti_toksisitas
        
    return costs

@app.get("/")
def read_root():
    return {"status": "aktif", "pesan": "Microservice Detail Analytics Siap!"}

@app.post("/predict")
def predict_optimal_fertilizer(data: LahanData):
    try:
        options = {'c1': 1.5, 'c2': 1.5, 'w': 0.5} 
        
        # [PERBAIKAN 4] Menurunkan batas atas pencarian (bounds) menjadi lebih realistis (25 kg)
        bounds = (np.array([0.0, 0.0, 0.0]), np.array([25.0, 25.0, 25.0]))
        
        optimizer = ps.single.GlobalBestPSO(n_particles=30, dimensions=3, options=options, bounds=bounds)
        
        best_cost, best_pos = optimizer.optimize(
            fitness_function, iters=50, 
            n_aktual=data.n_aktual, p_aktual=data.p_aktual, k_aktual=data.k_aktual,
            toleransi_ph=data.toleransi_ph, suhu=data.suhu, kelembaban=data.kelembaban, curah_hujan=data.curah_hujan,
            verbose=False 
        )
        
        rekomendasi_n = round(best_pos[0], 2)
        rekomendasi_p = round(best_pos[1], 2)
        rekomendasi_k = round(best_pos[2], 2)
        
        # Kalkulasi Ulang Titik Final dengan Scaler
        final_features = np.array([[data.n_aktual + rekomendasi_n, data.p_aktual + rekomendasi_p, data.k_aktual + rekomendasi_k, data.toleransi_ph, data.suhu, data.kelembaban, data.curah_hujan]])
        final_features_scaled = scaler.transform(final_features) # Transform final point
        
        estimasi_panen = round(knn_model.predict(final_features_scaled)[0], 2)
        estimasi_biaya = (rekomendasi_n * 12000) + (rekomendasi_p * 15000) + (rekomendasi_k * 14000)

        cost_history = optimizer.cost_history
        
        # Hitung Neighbors menggunakan scaled data
        distances, indices = knn_model.kneighbors(final_features_scaled)
        knn_details = []
        for i in range(len(indices[0])):
            idx = indices[0][i]
            knn_details.append({
                "tetangga_ke": i + 1,
                "jarak_euclidean": round(distances[0][i], 4),
                "yield_historis": round(y_train_global[idx], 2)
            })

        return {
            "rekomendasi_n": rekomendasi_n,
            "rekomendasi_p": rekomendasi_p,
            "rekomendasi_k": rekomendasi_k,
            "estimasi_panen": estimasi_panen,
            "estimasi_biaya": estimasi_biaya,
            "ai_log": {
                "dataset_rows": len(y_train_global),
                "pso_cost_history": cost_history,
                "knn_neighbors": knn_details
            }
        }

    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))