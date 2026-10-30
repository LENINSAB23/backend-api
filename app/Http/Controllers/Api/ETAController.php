<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ETAController extends Controller
{
    /**
     * Endpoint RESTful para predicción de ETA por Machine Learning
     * Endpoint: POST /api/predict-eta
     */
    public function predictEta(Request $request)
    {
        $validated = $request->validate([
            'origin_lat' => 'nullable|numeric',
            'origin_lng' => 'nullable|numeric',
            'dest_lat' => 'required|numeric',
            'dest_lng' => 'required|numeric',
            'prep_time_minutes' => 'nullable|integer|min:1',
            'is_rainy' => 'nullable|boolean',
        ]);

        $payload = [
            'origin_lat' => $validated['origin_lat'] ?? -9.9306,
            'origin_lng' => $validated['origin_lng'] ?? -76.2422,
            'dest_lat' => (float) $validated['dest_lat'],
            'dest_lng' => (float) $validated['dest_lng'],
            'prep_time_minutes' => $validated['prep_time_minutes'] ?? 15,
            'is_rainy' => $validated['is_rainy'] ?? false,
        ];

        try {
            // Intentar llamada al microservicio Python FastAPI en puerto 8001
            $response = Http::timeout(3)->post('http://127.0.0.1:8001/predict-eta', $payload);

            if ($response->successful()) {
                return response()->json($response->json());
            }
        } catch (\Exception $e) {
            // Fallback si el microservicio Python está inactivo
        }

        // Fallback matemático en Laravel si falla la conexión con el microservicio ML
        $distKm = $this->haversineKm($payload['origin_lat'], $payload['origin_lng'], $payload['dest_lat'], $payload['dest_lng']);
        $travelMins = round(($distKm / 20.0) * 60.0);
        $totalEta = max($payload['prep_time_minutes'] + $travelMins, 12);

        return response()->json([
            'status' => 'success',
            'estimated_minutes' => (int) $totalEta,
            'kitchen_prep_minutes' => $payload['prep_time_minutes'],
            'predicted_travel_time_minutes' => (int) $travelMins,
            'distance_km' => round($distKm, 2),
            'traffic_level' => 'Estimación Estándar (Fallback)',
            'optimal_route_summary' => "Ruta estimada: {$distKm} km hacia Huánuco.",
        ]);
    }

    private function haversineKm($lat1, $lon1, $lat2, $lon2)
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $r * $c;
    }
}
