<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_manage_catalog(): void
    {
        $product = Product::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/categories', ['name' => 'Frenos'])->assertForbidden();
        $this->deleteJson("/api/products/{$product->id}")->assertForbidden();
        $this->deleteJson("/api/categories/{$product->category_id}")->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_admin_can_manage_catalog(): void
    {
        $category = Category::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'Bujía NGK',
            'price' => 25,
        ])->assertCreated();

        $this->assertDatabaseHas('products', ['name' => 'Bujía NGK']);
    }
}
