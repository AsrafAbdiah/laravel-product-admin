<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_category_has_many_products_and_a_product_belongs_to_a_category(): void
    {
        $category = Category::factory()->create();
        $products = Product::factory()->count(3)->for($category)->create();

        $this->assertCount(3, $category->products);
        $this->assertTrue($products->first()->category->is($category));
    }

    #[Test]
    public function status_scope_filters_enabled_and_disabled(): void
    {
        Product::factory()->count(2)->create(['enabled' => true]);
        Product::factory()->disabled()->create();

        $this->assertSame(2, Product::status('enabled')->count());
        $this->assertSame(1, Product::status('disabled')->count());
        $this->assertSame(3, Product::status(null)->count());
    }

    #[Test]
    public function soft_deleted_products_are_excluded_from_queries(): void
    {
        $product = Product::factory()->create();
        $product->delete();

        $this->assertSame(0, Product::count());
        $this->assertSame(1, Product::withTrashed()->count());
    }

    #[Test]
    public function service_bulk_delete_only_soft_deletes_given_ids(): void
    {
        $products = Product::factory()->count(4)->create();

        $deleted = app(ProductService::class)->bulkDelete($products->take(2)->pluck('id')->all());

        $this->assertSame(2, $deleted);
        $this->assertSame(2, Product::count());
        $this->assertSame(4, Product::withTrashed()->count());
    }

    #[Test]
    public function price_is_cast_to_two_decimals(): void
    {
        $product = Product::factory()->create(['price' => 10]);

        $this->assertSame('10.00', $product->fresh()->price);
    }
}
