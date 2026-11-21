<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();

        $books = Category::factory()->create(['name' => 'Books']);
        $tools = Category::factory()->create(['name' => 'Tools']);

        // 4 active products: 3 enabled, 1 disabled, 2 at or below the low-stock threshold.
        Product::factory()->for($books)->create(['name' => 'Novel', 'price' => 20, 'stock' => 10, 'enabled' => true]);
        Product::factory()->for($books)->create(['name' => 'Atlas', 'price' => 50, 'stock' => 0, 'enabled' => true]);
        Product::factory()->for($tools)->create(['name' => 'Hammer', 'price' => 35.5, 'stock' => 100, 'enabled' => true]);
        Product::factory()->for($tools)->disabled()->create(['name' => 'Saw', 'price' => 80, 'stock' => 25]);

        // In the trash: must not count anywhere except in_trash.
        Product::factory()->for($books)->create(['price' => 999, 'stock' => 1])->delete();
    }

    #[Test]
    public function totals_are_calculated_correctly(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('totals.products', 4)
            ->assertJsonPath('totals.enabled', 3)
            ->assertJsonPath('totals.disabled', 1)
            ->assertJsonPath('totals.low_stock', 2)
            ->assertJsonPath('totals.in_trash', 1)
            // 20*10 + 50*0 + 35.5*100 + 80*25 = 200 + 0 + 3550 + 2000
            ->assertJsonPath('totals.inventory_value', '5750.00');
    }

    #[Test]
    public function lists_categories_low_stock_products_and_recent_changes(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('products_per_category.0.name', 'Books')
            ->assertJsonPath('products_per_category.0.products_count', 2)
            ->assertJsonPath('low_stock_products.0.name', 'Atlas')
            ->assertJsonPath('low_stock_products.1.name', 'Novel')
            ->assertJsonCount(2, 'low_stock_products')
            ->assertJsonStructure(['recent_stock_movements' => [['id', 'product' => ['id', 'name'], 'change', 'reason']]]);
    }

    #[Test]
    public function the_dashboard_uses_a_fixed_number_of_queries(): void
    {
        Sanctum::actingAs($this->admin);
        Product::factory()->count(30)->create();

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->getJson('/api/dashboard')->assertOk();

        // Auth lookups plus a handful of dashboard queries; never one per product.
        $this->assertLessThan(15, $queries);
    }

    #[Test]
    public function dashboard_page_shows_the_numbers(): void
    {
        $this->actingAs($this->admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Inventory value')
            ->assertSee('5,750.00')
            ->assertSee('Atlas')
            ->assertSee('Out of stock');
    }

    #[Test]
    public function home_redirects_to_the_dashboard(): void
    {
        $this->actingAs($this->admin)->get('/')->assertRedirect('/dashboard');
    }

    #[Test]
    public function guests_and_non_admins_cannot_see_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->getJson('/api/dashboard')->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/dashboard')->assertForbidden();
    }
}
