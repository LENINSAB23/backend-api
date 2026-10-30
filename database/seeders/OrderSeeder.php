<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('role', 'customer')->first();
        $driver = User::where('role', 'driver')->first();
        $products = Product::limit(3)->get();

        if ($products->count() < 2) {
            return;
        }

        // Order 1: In transit
        $order1 = Order::firstOrCreate(
            ['order_number' => 'RINO-20261010-001'],
            [
                'customer_id' => $customer ? $customer->id : null,
                'driver_id' => $driver ? $driver->id : null,
                'customer_name' => 'Maria Fernandez',
                'customer_phone' => '+51962888111',
                'delivery_address' => 'Jr. General Prado 450, Huánuco',
                'delivery_latitude' => -9.9325000,
                'delivery_longitude' => -76.2410000,
                'status' => 'in_transit',
                'payment_method' => 'yape',
                'payment_status' => 'paid',
                'subtotal' => 56.00,
                'delivery_fee' => 5.00,
                'total' => 61.00,
                'estimated_delivery_minutes' => 22,
                'estimated_delivery_at' => now()->addMinutes(15),
                'notes' => 'Tocar el timbre del 2do piso.',
            ]
        );

        OrderItem::firstOrCreate(
            ['order_id' => $order1->id, 'product_id' => $products[0]->id],
            [
                'product_name' => $products[0]->name,
                'quantity' => 1,
                'unit_price' => $products[0]->price,
                'subtotal' => $products[0]->price * 1,
            ]
        );

        OrderItem::firstOrCreate(
            ['order_id' => $order1->id, 'product_id' => $products[1]->id],
            [
                'product_name' => $products[1]->name,
                'quantity' => 1,
                'unit_price' => $products[1]->price,
                'subtotal' => $products[1]->price * 1,
            ]
        );

        // Order 2: Cooking
        $order2 = Order::firstOrCreate(
            ['order_number' => 'RINO-20261010-002'],
            [
                'customer_id' => $customer ? $customer->id : null,
                'driver_id' => null,
                'customer_name' => 'Jorge Ramos',
                'customer_phone' => '+51962555333',
                'delivery_address' => 'Av. Universitaria 1200, Cayhuayna, Huánuco',
                'delivery_latitude' => -9.9510000,
                'delivery_longitude' => -76.2450000,
                'status' => 'cooking',
                'payment_method' => 'card',
                'payment_status' => 'paid',
                'subtotal' => 38.00,
                'delivery_fee' => 5.00,
                'total' => 43.00,
                'estimated_delivery_minutes' => 30,
                'estimated_delivery_at' => now()->addMinutes(25),
                'notes' => 'Verificar compatibilidad con modelo 2022 y entregar en taller.',
            ]
        );

        OrderItem::firstOrCreate(
            ['order_id' => $order2->id, 'product_id' => $products[0]->id],
            [
                'product_name' => $products[0]->name,
                'quantity' => 1,
                'unit_price' => $products[0]->price,
                'subtotal' => $products[0]->price,
            ]
        );

        // Order 3: Pending
        Order::firstOrCreate(
            ['order_number' => 'RINO-20261010-003'],
            [
                'customer_id' => null,
                'driver_id' => null,
                'customer_name' => 'Carlos Benavides',
                'customer_phone' => '+51962111222',
                'delivery_address' => 'Jr. Dos de Mayo 780, Huánuco',
                'delivery_latitude' => -9.9290000,
                'delivery_longitude' => -76.2400000,
                'status' => 'pending',
                'payment_method' => 'yape',
                'payment_status' => 'pending',
                'subtotal' => 26.50,
                'delivery_fee' => 5.00,
                'total' => 31.50,
                'estimated_delivery_minutes' => 25,
                'notes' => 'Pedido registrado vía WhatsApp Chatbot.',
            ]
        );
    }
}
