<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->admin()->create(), ['*']);
    }

    #[Test]
    public function admin_can_list_categories_with_product_counts(): void
    {
        $books = Category::factory()->create(['name' => 'Books']);
        Product::factory()->count(2)->for($books)->create();
        Category::factory()->create(['name' => 'Apparel']);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Apparel')
            ->assertJsonPath('data.0.products_count', 0)
            ->assertJsonPath('data.1.name', 'Books')
            ->assertJsonPath('data.1.products_count', 2);
    }

    #[Test]
    public function admin_can_create_a_category(): void
    {
        $this->postJson('/api/categories', ['name' => 'Garden'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Garden')
            ->assertJsonPath('data.products_count', 0);

        $this->assertDatabaseHas('categories', ['name' => 'Garden']);
    }

    #[Test]
    public function category_name_is_required_and_unique(): void
    {
        Category::factory()->create(['name' => 'Garden']);

        $this->postJson('/api/categories', [])->assertStatus(422)->assertJsonValidationErrors('name');
        $this->postJson('/api/categories', ['name' => 'Garden'])->assertStatus(422)->assertJsonValidationErrors('name');
    }

    #[Test]
    public function admin_can_view_and_rename_a_category(): void
    {
        $category = Category::factory()->create(['name' => 'Toys']);

        $this->getJson("/api/categories/{$category->id}")->assertOk()->assertJsonPath('data.name', 'Toys');

        $this->putJson("/api/categories/{$category->id}", ['name' => 'Toys & Games'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Toys & Games');
    }

    #[Test]
    public function saving_a_category_without_renaming_it_passes_the_unique_check(): void
    {
        $category = Category::factory()->create(['name' => 'Toys']);

        $this->putJson("/api/categories/{$category->id}", ['name' => 'Toys'])->assertOk();
    }

    #[Test]
    public function an_empty_category_can_be_deleted(): void
    {
        $category = Category::factory()->create();

        $this->deleteJson("/api/categories/{$category->id}")->assertOk();

        $this->assertModelMissing($category);
    }

    #[Test]
    public function a_category_with_products_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();

        $this->deleteJson("/api/categories/{$category->id}")->assertStatus(409);

        $this->assertModelExists($category);
    }

    #[Test]
    public function products_in_the_trash_also_block_deleting_their_category(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create()->delete();

        $this->deleteJson("/api/categories/{$category->id}")->assertStatus(409);

        $this->assertModelExists($category);
    }
}
