<?php

namespace Tests\Feature\Api;

use App\Exports\ProductsExport;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->admin()->create());
    }

    #[Test]
    public function admin_can_create_a_product(): void
    {
        $category = Category::factory()->create(['name' => 'Electronics']);

        $response = $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'Wireless Mouse',
            'description' => 'Ergonomic mouse',
            'price' => 49.9,
            'stock' => 120,
            'enabled' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Wireless Mouse')
            ->assertJsonPath('data.price', '49.90')
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.category.name', 'Electronics');

        $this->assertDatabaseHas('products', ['name' => 'Wireless Mouse', 'stock' => 120]);
    }

    #[Test]
    public function product_is_enabled_by_default(): void
    {
        $category = Category::factory()->create();

        $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'Desk Lamp',
            'price' => 30,
            'stock' => 5,
        ])->assertCreated()->assertJsonPath('data.enabled', true);
    }

    #[Test]
    public function creating_a_product_validates_input(): void
    {
        $this->postJson('/api/products', [
            'category_id' => 999,
            'name' => '',
            'price' => -5,
            'stock' => 'many',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'name', 'price', 'stock']);

        $this->assertDatabaseCount('products', 0);
    }

    #[Test]
    public function price_cannot_have_more_than_two_decimals(): void
    {
        $category = Category::factory()->create();

        $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'Pen',
            'price' => 1.999,
            'stock' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('price');
    }

    #[Test]
    public function admin_can_list_products_with_pagination(): void
    {
        Product::factory()->count(20)->create();

        $this->getJson('/api/products?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonStructure(['data' => [['id', 'name', 'price', 'stock', 'enabled', 'category_id', 'category' => ['id', 'name']]], 'links', 'meta']);
    }

    #[Test]
    public function products_can_be_filtered_by_status(): void
    {
        Product::factory()->count(3)->create(['enabled' => true]);
        Product::factory()->count(2)->disabled()->create();

        $this->getJson('/api/products?status=enabled')->assertOk()->assertJsonPath('meta.total', 3);
        $this->getJson('/api/products?status=disabled')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/api/products?status=unknown')->assertStatus(422);
    }

    #[Test]
    public function products_can_be_filtered_by_category_and_search(): void
    {
        $books = Category::factory()->create();
        Product::factory()->count(2)->for($books)->create();
        Product::factory()->for($books)->create(['name' => 'Laravel Handbook']);
        Product::factory()->count(4)->create();

        $this->getJson("/api/products?category_id={$books->id}")->assertOk()->assertJsonPath('meta.total', 3);
        $this->getJson('/api/products?search=Handbook')->assertOk()->assertJsonPath('meta.total', 1);
    }

    #[Test]
    public function products_can_be_filtered_by_low_stock(): void
    {
        Product::factory()->count(2)->create(['stock' => 5]);
        Product::factory()->create(['stock' => Product::LOW_STOCK_THRESHOLD]);
        Product::factory()->count(3)->create(['stock' => 50]);

        $this->getJson('/api/products?low_stock=1')->assertOk()->assertJsonPath('meta.total', 3);
        $this->getJson('/api/products')->assertOk()->assertJsonPath('meta.total', 6);
    }

    #[Test]
    public function products_can_be_sorted_by_price(): void
    {
        Product::factory()->create(['price' => 30]);
        Product::factory()->create(['price' => 10]);
        Product::factory()->create(['price' => 20]);

        $this->getJson('/api/products?sort=price')
            ->assertOk()
            ->assertJsonPath('data.0.price', '10.00')
            ->assertJsonPath('data.2.price', '30.00');

        $this->getJson('/api/products?sort=-price')
            ->assertOk()
            ->assertJsonPath('data.0.price', '30.00');
    }

    #[Test]
    public function sorting_only_allows_whitelisted_fields(): void
    {
        $this->getJson('/api/products?sort=password')->assertStatus(422)->assertJsonValidationErrors('sort');
        $this->getJson('/api/products?sort=price;DROP TABLE products')->assertStatus(422);
    }

    #[Test]
    public function per_page_is_capped(): void
    {
        $this->getJson('/api/products?per_page=1000')->assertStatus(422)->assertJsonValidationErrors('per_page');
    }

    #[Test]
    public function admin_can_view_a_single_product_with_its_category(): void
    {
        $product = Product::factory()->create();

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.category.id', $product->category_id)
            ->assertJsonPath('data.category.name', $product->category->name);
    }

    #[Test]
    public function missing_product_returns_clean_404(): void
    {
        $this->getJson('/api/products/999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Resource not found.']);
    }

    #[Test]
    public function admin_can_update_a_product(): void
    {
        $product = Product::factory()->create(['name' => 'Old name', 'stock' => 1]);

        $this->putJson("/api/products/{$product->id}", ['name' => 'New name', 'stock' => 99])
            ->assertOk()
            ->assertJsonPath('data.name', 'New name')
            ->assertJsonPath('data.stock', 99);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'New name', 'stock' => 99]);
    }

    #[Test]
    public function admin_can_soft_delete_a_product(): void
    {
        $product = Product::factory()->create();

        $this->deleteJson("/api/products/{$product->id}")->assertOk();

        $this->assertSoftDeleted($product);
        $this->getJson("/api/products/{$product->id}")->assertNotFound();
    }

    #[Test]
    public function admin_can_bulk_delete_products(): void
    {
        $toDelete = Product::factory()->count(3)->create();
        $toKeep = Product::factory()->create();

        $this->deleteJson('/api/products/bulk', ['ids' => $toDelete->pluck('id')->all()])
            ->assertOk()
            ->assertJsonPath('deleted', 3);

        $toDelete->each(fn (Product $p) => $this->assertSoftDeleted($p));
        $this->assertNotSoftDeleted($toKeep);
    }

    #[Test]
    public function bulk_delete_rejects_invalid_ids(): void
    {
        $deleted = Product::factory()->create();
        $deleted->delete();

        $this->deleteJson('/api/products/bulk', ['ids' => []])->assertStatus(422)->assertJsonValidationErrors('ids');
        $this->deleteJson('/api/products/bulk', ['ids' => [999]])->assertStatus(422);
        $this->deleteJson('/api/products/bulk', ['ids' => [$deleted->id]])->assertStatus(422);
    }

    #[Test]
    public function admin_can_export_products_to_excel(): void
    {
        Excel::fake();
        Excel::matchByRegex();
        Product::factory()->count(3)->create(['enabled' => true]);
        Product::factory()->disabled()->create();

        $this->get('/api/products/export?status=enabled')->assertOk();

        Excel::assertDownloaded('/^products-\d{8}-\d{6}\.xlsx$/', function (ProductsExport $export) {
            // The export respects the same filters as the list endpoint.
            return $export->query()->count() === 3;
        });
    }
}
