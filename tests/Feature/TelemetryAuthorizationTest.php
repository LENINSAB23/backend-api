<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TelemetryAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_send_or_read_telemetry(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);

        $this->postJson('/api/telemetry/location', ['latitude' => -9.93, 'longitude' => -76.24])->assertUnauthorized();
        $this->getJson("/api/telemetry/latest/{$driver->id}")->assertUnauthorized();
    }

    public function test_customer_cannot_send_telemetry(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/telemetry/location', ['latitude' => -9.93, 'longitude' => -76.24])->assertForbidden();
    }

    public function test_telemetry_is_stored_for_the_authenticated_driver_not_the_body_driver_id(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $otherDriver = User::factory()->create(['role' => 'driver']);
        Sanctum::actingAs($driver);

        $this->postJson('/api/telemetry/location', [
            'driver_id' => $otherDriver->id,
            'latitude' => -9.93,
            'longitude' => -76.24,
        ])->assertCreated();

        $this->assertDatabaseHas('driver_telemetries', ['driver_id' => $driver->id]);
        $this->assertDatabaseMissing('driver_telemetries', ['driver_id' => $otherDriver->id]);
    }

    public function test_driver_cannot_attach_telemetry_to_an_order_assigned_to_someone_else(): void
    {
        $order = Order::factory()->forDriver()->create();
        Sanctum::actingAs(User::factory()->create(['role' => 'driver']));

        $this->postJson('/api/telemetry/location', [
            'order_id' => $order->id,
            'latitude' => -9.93,
            'longitude' => -76.24,
        ])->assertUnprocessable()->assertJsonValidationErrors('order_id');
    }

    public function test_only_admin_can_read_a_drivers_latest_location(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/telemetry/latest/{$driver->id}")->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $this->getJson("/api/telemetry/latest/{$driver->id}")->assertNotFound();
    }

    public function test_customer_can_track_their_own_order(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        Sanctum::actingAs($customer);

        $this->getJson("/api/telemetry/order/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.order.id', $order->id);
    }
}
