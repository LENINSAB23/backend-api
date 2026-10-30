<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['items.product', 'driver', 'customer', 'latestTelemetry'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $orders = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $orders,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'delivery_address' => 'required|string',
            'delivery_latitude' => 'nullable|numeric',
            'delivery_longitude' => 'nullable|numeric',
            'payment_method' => 'nullable|string|in:yape,card,cash',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $subtotal = 0;
            $itemsToCreate = [];

            foreach ($validated['items'] as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $itemSubtotal = $product->price * $itemData['quantity'];
                $subtotal += $itemSubtotal;

                $itemsToCreate[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $deliveryFee = 5.00; // tarifa base Huánuco
            $total = $subtotal + $deliveryFee;
            $orderNumber = 'RINO-'.date('Ymd').'-'.strtoupper(Str::random(4));

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $request->user() ? $request->user()->id : null,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'delivery_address' => $validated['delivery_address'],
                'delivery_latitude' => $validated['delivery_latitude'] ?? -9.9306,
                'delivery_longitude' => $validated['delivery_longitude'] ?? -76.2422,
                'status' => 'pending',
                'payment_method' => $validated['payment_method'] ?? 'yape',
                'payment_status' => 'pending',
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'estimated_delivery_minutes' => 25,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($itemsToCreate as $item) {
                $item['order_id'] = $order->id;
                OrderItem::create($item);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Orden registrada con éxito',
                'data' => $order->load('items'),
            ], 201);
        });
    }

    public function show($id)
    {
        $order = Order::with(['items.product', 'driver', 'customer', 'telemetries' => function ($q) {
            $q->orderBy('recorded_at', 'desc')->limit(20);
        }])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $order,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:pending,cooking,in_transit,delivered,cancelled',
            'payment_status' => 'nullable|string|in:pending,paid,failed',
        ]);

        $order->status = $validated['status'];
        if (isset($validated['payment_status'])) {
            $order->payment_status = $validated['payment_status'];
        }
        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => "Estado de orden actualizado a '{$order->status}'",
            'data' => $order,
        ]);
    }

    public function assignDriver(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'driver_id' => 'required|exists:users,id',
        ]);

        $order->driver_id = $validated['driver_id'];
        if ($order->status === 'pending' || $order->status === 'cooking') {
            $order->status = 'in_transit';
        }
        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Repartidor asignado correctamente',
            'data' => $order->load('driver'),
        ]);
    }
}
