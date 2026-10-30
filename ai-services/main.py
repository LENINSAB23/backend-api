import os
import sys
import math
from datetime import datetime
from typing import Optional
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import joblib
import numpy as np

sys.stdout.reconfigure(encoding='utf-8')

app = FastAPI(
    title="Grupo Rino - AI/ML Microservice (ETA & Optimal Route)",
    description="Microservicio de Inteligencia Artificial para estimación precisa de ETA en Huánuco, Perú",
    version="1.0.0"
)

# Configurar CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

MODEL_PATH = os.path.join(os.path.dirname(__file__), 'eta_model.joblib')
ml_model = None

@app.on_event("startup")
def load_model():
    global ml_model
    if os.path.exists(MODEL_PATH):
        try:
            ml_model = joblib.load(MODEL_PATH)
            print(f"[AI-ML] Modelo cargado exitosamente desde {MODEL_PATH}")
        except Exception as e:
            print(f"[AI-ML] Error al cargar el modelo: {e}")
    else:
        print("[AI-ML] ADVERTENCIA: eta_model.joblib no existe. Ejecute train_eta_model.py primero.")

class ETARequest(BaseModel):
    origin_lat: float = -9.9306
    origin_lng: float = -76.2422
    dest_lat: float
    dest_lng: float
    prep_time_minutes: Optional[int] = 15
    is_rainy: Optional[bool] = False

def haversine_km(lat1, lon1, lat2, lon2):
    R = 6371.0
    dlat = math.radians(lat2 - lat1)
    dlon = math.radians(lon2 - lon1)
    a = (math.sin(dlat / 2) ** 2 +
         math.cos(math.radians(lat1)) * math.cos(math.radians(lat2)) * math.sin(dlon / 2) ** 2)
    c = 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a))
    return R * c

def get_huanuco_traffic_factor(hour: int) -> tuple[float, str]:
    if (12 <= hour <= 14) or (18 <= hour <= 20):
        return 0.55, "Alta congestión (Hora punta Huánuco)"
    elif (8 <= hour <= 10) or (17 <= hour <= 18):
        return 0.30, "Tráfico moderado"
    else:
        return 0.10, "Tráfico fluido"

@app.get("/health")
def health_check():
    return {
        "status": "ok",
        "service": "Grupo Rino AI/ML ETA Predictor",
        "model_loaded": ml_model is not None,
        "city": "Huánuco, Perú"
    }

@app.post("/predict-eta")
def predict_eta(req: ETARequest):
    distance_km = haversine_km(req.origin_lat, req.origin_lng, req.dest_lat, req.dest_lng)
    
    now = datetime.now()
    hour = now.hour
    day_of_week = now.weekday()
    
    traffic_factor, traffic_desc = get_huanuco_traffic_factor(hour)
    prep_time = req.prep_time_minutes or 15
    is_rainy_int = 1 if req.is_rainy else 0

    if ml_model is not None:
        features = np.array([[
            distance_km,
            hour,
            day_of_week,
            prep_time,
            traffic_factor,
            is_rainy_int
        ]])
        predicted_minutes = float(ml_model.predict(features)[0])
    else:
        # Fallback fórmula matemática si modelo no disponible
        estimated_speed = 25.0 * (1.0 - traffic_factor * 0.4)
        travel_mins = (distance_km / estimated_speed) * 60.0
        predicted_minutes = prep_time + travel_mins + (is_rainy_int * 5.0)

    predicted_minutes = max(round(predicted_minutes), 10)
    travel_time_only = max(round(predicted_minutes - prep_time), 3)

    # Resumen de ruta óptima
    route_summary = f"Ruta óptima en Huánuco: {distance_km:.2f} km vía Jr. 2 de Mayo / Av. Universitaria."
    if req.is_rainy:
        route_summary += " (Precaución por pista mojada)"

    return {
        "status": "success",
        "estimated_minutes": predicted_minutes,
        "kitchen_prep_minutes": prep_time,
        "predicted_travel_time_minutes": travel_time_only,
        "distance_km": round(distance_km, 2),
        "traffic_level": traffic_desc,
        "optimal_route_summary": route_summary,
        "features_evaluated": {
            "hour": hour,
            "day_of_week": day_of_week,
            "is_rainy": req.is_rainy
        }
    }

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8001)
