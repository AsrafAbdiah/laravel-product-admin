<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrashApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->admin()->create(), ['*']);
    }

    #[Test]
    public function trash_lists_only_deleted_products(): void
    {
        Product::factory()->count(2)->create();
        $deleted = Product::factory()->count(3)->create()->each->delete();

        $this->getJson('/api/products/trash')
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure(['data' => [['id', 'name', 'deleted_at']]]);

        // The normal list is unaffected.
        $this->getJson('/api/products')->assertJsonPath('meta.total', 2);
        $this->assertCount(3, $deleted);
    }

    #[Test]
    public function trash_can_be_searched(): void
    {
        Product::factory()->create(['name' => 'Old Keyboard'])->delete();
        Product::factory()->create(['name' => 'Old Monitor'])->delete();

        $this->getJson('/api/products/trash?search=Keyboard')->assertOk()->assertJsonPath('meta.total', 1);
    }

    #[Test]
    public function a_deleted_product_can_be_restored(): void
    {
        $product = Product::factory()->create();
        $product->delete();

        $this->patchJson("/api/products/{$product->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonMissingPath('data.deleted_at');

        $this->assertNotSoftDeleted($product);
        $this->getJson("/api/products/{$product->id}")->assertOk();
    }

    #[Test]
    public function a_deleted_product_can_be_permanently_deleted(): void
    {
        $product = Product::factory()->create();
        $product->delete();

        $this->deleteJson("/api/products/{$product->id}/force")->assertOk();

        $this->assertModelMissing($product);
    }

    #[Test]
    public function restoring_a_product_that_is_not_in_the_trash_is_a_conflict(): void
    {
        $product = Product::factory()->create();

        $this->patchJson("/api/products/{$product->id}/restore")
            ->assertStatus(409)
            ->assertJsonPath('message', 'This product is not in the trash.');
    }

    #[Test]
    public function a_product_must_be_in_the_trash_before_it_can_be_permanently_deleted(): void
    {
        $product = Product::factory()->create();

        $this->deleteJson("/api/products/{$product->id}/force")->assertStatus(409);

        $this->assertNotSoftDeleted($product);
    }

    #[Test]
    public function unknown_products_return_404(): void
    {
        $this->patchJson('/api/products/999/restore')->assertNotFound();
        $this->deleteJson('/api/products/999/force')->assertNotFound();
    }
}
