<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_orders(): void
    {
        $order = Order::factory()->create();

        $this->getJson('/api/orders')->assertUnauthorized();
        $this->getJson("/api/orders/{$order->id}")->assertUnauthorized();
        $this->postJson('/api/orders', [])->assertUnauthorized();
        $this->putJson("/api/orders/{$order->id}/status", ['status' => 'delivered'])->assertUnauthorized();
    }

    public function test_registration_ignores_requested_admin_role(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Intruso',
            'email' => 'intruso@example.com',
            'password' => 'secret123',
            'role' => 'admin',
        ]);

        $response->assertCreated()->assertJsonPath('data.user.role', 'customer');
        $this->assertDatabaseHas('users', ['email' => 'intruso@example.com', 'role' => 'customer']);
    }

    public function test_created_order_is_linked_to_the_authenticated_customer(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create(['price' => 20]);
        Sanctum::actingAs($customer);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Maria',
            'customer_phone' => '999888777',
            'delivery_address' => 'Jr. Huallayco 123',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertCreated()->assertJsonPath('data.customer_id', $customer->id);
    }

    public function test_customer_only_lists_their_own_orders(): void
    {
        $customer = User::factory()->create();
        $ownOrder = Order::factory()->create(['customer_id' => $customer->id]);
        Order::factory()->create();
        Sanctum::actingAs($customer);

        $response = $this->getJson('/api/orders?customer_id=999');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownOrder->id);
    }

    public function test_driver_lists_assigned_and_unassigned_orders_only(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $assigned = Order::factory()->forDriver($driver)->create();
        $unassigned = Order::factory()->create();
        Order::factory()->forDriver()->create();
        Sanctum::actingAs($driver);

        $ids = $this->getJson('/api/orders')->assertOk()->json('data.*.id');

        $this->assertEqualsCanonicalizing([$assigned->id, $unassigned->id], $ids);
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $order = Order::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/orders/{$order->id}")->assertForbidden();
        $this->getJson("/api/telemetry/order/{$order->id}")->assertForbidden();
    }

    public function test_customer_cannot_change_order_status(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        Sanctum::actingAs($customer);

        $this->putJson("/api/orders/{$order->id}/status", [
            'status' => 'delivered',
            'payment_status' => 'paid',
        ])->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'pending']);
    }

    public function test_assigned_driver_can_change_order_status(): void
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $order = Order::factory()->forDriver($driver)->create();
        Sanctum::actingAs($driver);

        $this->putJson("/api/orders/{$order->id}/status", ['status' => 'delivered'])->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'delivered']);
    }

    public function test_other_driver_cannot_change_order_status(): void
    {
        $order = Order::factory()->forDriver()->create();
        Sanctum::actingAs(User::factory()->create(['role' => 'driver']));

        $this->putJson("/api/orders/{$order->id}/status", ['status' => 'delivered'])->assertForbidden();
    }

    public function test_only_admin_can_assign_a_driver(): void
    {
        $order = Order::factory()->create();
        $driver = User::factory()->create(['role' => 'driver']);

        Sanctum::actingAs($driver);
        $this->putJson("/api/orders/{$order->id}/assign-driver", ['driver_id' => $driver->id])->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $this->putJson("/api/orders/{$order->id}/assign-driver", ['driver_id' => $driver->id])
            ->assertOk()
            ->assertJsonPath('data.driver_id', $driver->id);
    }

    public function test_assigned_user_must_have_driver_role(): void
    {
        $order = Order::factory()->create();
        $customer = User::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->putJson("/api/orders/{$order->id}/assign-driver", ['driver_id' => $customer->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('driver_id');
    }
}
