from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import pandas as pd
import numpy as np
from sklearn.neighbors import KNeighborsRegressor
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

# Simpan dataset ke memory global agar bisa diakses untuk visualisasi detail
X_train_global = None
y_train_global = None

def prepare_model():
    global knn_model, X_train_global, y_train_global
    try:
        df = pd.read_csv(DATASET_FILE)
        X_train_global = df[['N_ratio', 'P_ratio', 'K_ratio', 'Soil_pH', 'Temperature_C', 'Humidity_pct', 'Rainfall_mm']].values
        y_train_global = df['Yield_ton_per_ha'].values
        
        knn_model.fit(X_train_global, y_train_global)
        print("=====================================================")
        print(f"SUCCESS: Model KNN dilatih dengan dataset '{DATASET_FILE}'")
        print(f"Total Fitur     : {X_train_global.shape[1]} Variabel")
        print(f"Total Baris Data: {len(df)} Baris")
        print("=====================================================")
    except FileNotFoundError:
        print(f"ERROR: File '{DATASET_FILE}' tidak ditemukan di folder smart-agri-ai.")
    except Exception as e:
        print(f"ERROR SAAT MEMBACA DATASET: {e}")

prepare_model()

def fitness_function(particles, n_aktual, p_aktual, k_aktual, toleransi_ph, suhu, kelembaban, curah_hujan):
    n_particles = particles.shape[0]
    costs = np.zeros(n_particles)
    HARGA_N = 12000; HARGA_P = 15000; HARGA_K = 14000; HARGA_PANEN_PER_TON = 6000000 

    for i in range(n_particles):
        delta_n, delta_p, delta_k = particles[i]
        biaya_pupuk = (delta_n * HARGA_N) + (delta_p * HARGA_P) + (delta_k * HARGA_K)
        input_features = np.array([[n_aktual + delta_n, p_aktual + delta_p, k_aktual + delta_k, toleransi_ph, suhu, kelembaban, curah_hujan]])
        
        prediksi_panen = knn_model.predict(input_features)[0]
        costs[i] = biaya_pupuk - (prediksi_panen * HARGA_PANEN_PER_TON)
        
    return costs

@app.get("/")
def read_root():
    return {"status": "aktif", "pesan": "Microservice Detail Analytics Siap!"}

@app.post("/predict")
def predict_optimal_fertilizer(data: LahanData):
    try:
        options = {'c1': 1.5, 'c2': 1.5, 'w': 0.5} 
        bounds = (np.array([0.0, 0.0, 0.0]), np.array([50.0, 50.0, 50.0]))
        
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
        
        final_features = np.array([[data.n_aktual + rekomendasi_n, data.p_aktual + rekomendasi_p, data.k_aktual + rekomendasi_k, data.toleransi_ph, data.suhu, data.kelembaban, data.curah_hujan]])
        estimasi_panen = round(knn_model.predict(final_features)[0], 2)
        estimasi_biaya = (rekomendasi_n * 12000) + (rekomendasi_p * 15000) + (rekomendasi_k * 14000)

        # MENGAMBIL DATA DETAIL UNTUK PRESENTASI
        # 1. Ambil Cost History dari tiap iterasi PSO
        cost_history = optimizer.cost_history
        
        # 2. Ambil 5 Titik Tetangga Terdekat dari algoritma KNN
        distances, indices = knn_model.kneighbors(final_features)
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