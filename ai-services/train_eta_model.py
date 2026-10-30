import math
import sys
import os

# Fix Windows stdout encoding for print
sys.stdout.reconfigure(encoding='utf-8')

import numpy as np
import pandas as pd
from sklearn.ensemble import GradientBoostingRegressor
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_absolute_error, r2_score
import joblib

def haversine_km(lat1, lon1, lat2, lon2):
    """Calcula la distancia Haversine en km entre dos coordenadas GPS."""
    R = 6371.0 # Radio de la Tierra en km
    dlat = math.radians(lat2 - lat1)
    dlon = math.radians(lon2 - lon1)
    a = (math.sin(dlat / 2) ** 2 +
         math.cos(math.radians(lat1)) * math.cos(math.radians(lat2)) * math.sin(dlon / 2) ** 2)
    c = 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a))
    return R * c

def generate_huanuco_dataset(n_samples=2500, seed=42):
    """
    Genera un dataset sintético realista de deliveries en Huánuco, Perú.
    """
    np.random.seed(seed)
    
    # Coordenadas centro de Huánuco (Plaza de Armas / Cayhuayna / Amarilis)
    huanuco_center_lat = -9.9306
    huanuco_center_lng = -76.2422
    
    # Generar variaciones de lat/lng dentro de 5 km
    lat_offsets = np.random.uniform(-0.03, 0.03, n_samples)
    lng_offsets = np.random.uniform(-0.03, 0.03, n_samples)
    
    origin_lats = np.full(n_samples, huanuco_center_lat)
    origin_lngs = np.full(n_samples, huanuco_center_lng)
    
    dest_lats = origin_lats + lat_offsets
    dest_lngs = origin_lngs + lng_offsets
    
    distances_km = [
        haversine_km(origin_lats[i], origin_lngs[i], dest_lats[i], dest_lngs[i])
        for i in range(n_samples)
    ]
    distances_km = np.array(distances_km)
    
    hours_of_day = np.random.randint(8, 23, n_samples)
    days_of_week = np.random.randint(0, 7, n_samples)
    prep_times = np.random.choice([10, 15, 20, 25, 30], size=n_samples, p=[0.2, 0.3, 0.3, 0.15, 0.05])
    is_rainy = np.random.choice([0, 1], size=n_samples, p=[0.8, 0.2])
    
    # Factor de tráfico en Huánuco (Horas punta: 12-14h y 18-20h)
    traffic_factors = []
    for h in hours_of_day:
        if (12 <= h <= 14) or (18 <= h <= 20):
            traffic_factors.append(np.random.uniform(0.4, 0.7)) # Congestión alta
        elif (8 <= h <= 10) or (17 <= h <= 18):
            traffic_factors.append(np.random.uniform(0.2, 0.4)) # Tráfico moderado
        else:
            traffic_factors.append(np.random.uniform(0.05, 0.2)) # Tráfico fluido
            
    traffic_factors = np.array(traffic_factors)
    
    # Velocidad promedio base en moto (25 km/h en Huánuco)
    base_speed_kmh = 25.0
    effective_speed_kmh = base_speed_kmh * (1.0 - traffic_factors * 0.4)
    
    travel_time_minutes = (distances_km / effective_speed_kmh) * 60.0
    
    # Retraso por lluvia en Huánuco (+20% tiempo de viaje + 3 min extra de precaución)
    rain_delay = is_rainy * (travel_time_minutes * 0.2 + 3.0)
    
    # Ruido gaussiano (1 a 3 minutos)
    noise = np.random.normal(0, 1.5, n_samples)
    
    actual_delivery_minutes = prep_times + travel_time_minutes + rain_delay + noise
    actual_delivery_minutes = np.maximum(actual_delivery_minutes, 10.0) # Mínimo 10 min
    
    df = pd.DataFrame({
        'distance_km': distances_km,
        'hour_of_day': hours_of_day,
        'day_of_week': days_of_week,
        'prep_time_minutes': prep_times,
        'traffic_factor': traffic_factors,
        'is_rainy': is_rainy,
        'actual_delivery_minutes': actual_delivery_minutes
    })
    
    return df

def train_and_save():
    print("[AI-ML] Entrenando Modelo para Prediccion de ETA (Huanuco Delivery)...")
    df = generate_huanuco_dataset(n_samples=2500)
    
    X = df[['distance_km', 'hour_of_day', 'day_of_week', 'prep_time_minutes', 'traffic_factor', 'is_rainy']]
    y = df['actual_delivery_minutes']
    
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)
    
    model = GradientBoostingRegressor(
        n_estimators=150,
        learning_rate=0.08,
        max_depth=4,
        random_state=42
    )
    
    model.fit(X_train, y_train)
    
    y_pred = model.predict(X_test)
    mae = mean_absolute_error(y_test, y_pred)
    r2 = r2_score(y_test, y_pred)
    
    print(f"[AI-ML] Modelo entrenado exitosamente!")
    print(f"[AI-ML] Error Absoluto Medio (MAE): {mae:.2f} minutos")
    print(f"[AI-ML] Coeficiente de Determinacion (R2): {r2:.4f}")
    
    model_path = os.path.join(os.path.dirname(__file__), 'eta_model.joblib')
    joblib.dump(model, model_path)
    print(f"[AI-ML] Modelo guardado en: {model_path}")

if __name__ == '__main__':
    train_and_save()
