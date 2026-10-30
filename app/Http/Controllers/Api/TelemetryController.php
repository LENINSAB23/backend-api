<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverTelemetry;
use App\Models\Order;
use Illuminate\Http\Request;

class TelemetryController extends Controller
{
    /**
     * Endpoint para recibir los paquetes telemáticos desde la App Flutter (Nodo IoT Repartidor)
     */
    public function storeLocation(Request $request)
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:users,id',
            'order_id' => 'nullable|exists:orders,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'altitude' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'battery_level' => 'nullable|integer',
            'recorded_at' => 'nullable|date',
        ]);

        $telemetry = DriverTelemetry::create([
            'driver_id' => $validated['driver_id'],
            'order_id' => $validated['order_id'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'altitude' => $validated['altitude'] ?? null,
            'speed' => $validated['speed'] ?? null,
            'battery_level' => $validated['battery_level'] ?? null,
            'recorded_at' => $validated['recorded_at'] ?? now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Paquete telemático registrado',
            'data' => $telemetry,
        ], 201);
    }

    /**
     * Obtener la última posición satelital de un repartidor
     */
    public function latestDriverLocation($driverId)
    {
        $telemetry = DriverTelemetry::where('driver_id', $driverId)
            ->latest('recorded_at')
            ->first();

        if (! $telemetry) {
            return response()->json([
                'status' => 'error',
                'message' => 'No hay registros telemáticos para este repartidor',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $telemetry,
        ]);
    }

    /**
     * Obtener el historial telemático y posición actual para el rastreo de un pedido en tiempo real
     */
    public function orderTracking($orderId)
    {
        $order = Order::with('driver')->findOrFail($orderId);

        $telemetries = DriverTelemetry::where('order_id', $orderId)
            ->orWhere(function ($query) use ($order) {
                if ($order->driver_id) {
                    $query->where('driver_id', $order->driver_id);
                }
            })
            ->orderBy('recorded_at', 'desc')
            ->limit(50)
            ->get();

        $latestLocation = $telemetries->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'delivery_address' => $order->delivery_address,
                    'delivery_latitude' => $order->delivery_latitude,
                    'delivery_longitude' => $order->delivery_longitude,
                    'estimated_delivery_minutes' => $order->estimated_delivery_minutes,
                ],
                'driver' => $order->driver,
                'current_location' => $latestLocation,
                'history' => $telemetries,
            ],
        ]);
    }
}
