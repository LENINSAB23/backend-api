<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'RINO-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'customer_id' => User::factory(),
            'driver_id' => null,
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('9########'),
            'delivery_address' => fake()->address(),
            'delivery_latitude' => -9.9306,
            'delivery_longitude' => -76.2422,
            'status' => 'pending',
            'payment_method' => 'yape',
            'payment_status' => 'pending',
            'subtotal' => 100,
            'delivery_fee' => 5,
            'total' => 105,
            'estimated_delivery_minutes' => 25,
        ];
    }

    /**
     * Assign the order to a driver.
     */
    public function forDriver(?User $driver = null): static
    {
        return $this->state(fn (): array => [
            'driver_id' => $driver?->id ?? User::factory()->state(['role' => 'driver']),
            'status' => 'in_transit',
        ]);
    }
}
