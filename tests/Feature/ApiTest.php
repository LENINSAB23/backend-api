<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_categories()
    {
        $response = $this->getJson('/api/categories');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data']);
    }

    public function test_can_fetch_products()
    {
        $response = $this->getJson('/api/products');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data']);
    }

    public function test_can_receive_telemetry_location()
    {
        $driver = User::factory()->create(['role' => 'driver']);

        $response = $this->postJson('/api/telemetry/location', [
            'driver_id' => $driver->id,
            'latitude' => -9.9306,
            'longitude' => -76.2422,
            'altitude' => 1900.5,
            'speed' => 35.2,
            'battery_level' => 88,
        ]);

        $response->assertStatus(201)
            ->assertJson(['status' => 'success']);
    }

    public function test_can_fetch_orders()
    {
        $response = $this->getJson('/api/orders');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data']);
    }

    public function test_can_predict_eta_with_ml()
    {
        $response = $this->postJson('/api/predict-eta', [
            'origin_lat' => -9.9306,
            'origin_lng' => -76.2422,
            'dest_lat' => -9.9510,
            'dest_lng' => -76.2450,
            'prep_time_minutes' => 15,
            'is_rainy' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'estimated_minutes', 'distance_km', 'optimal_route_summary']);
    }
}
