<?php

namespace Tests\Feature\Web;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryWebTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    #[Test]
    public function guest_and_non_admin_cannot_manage_categories(): void
    {
        $this->get('/categories')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/categories')->assertForbidden();
    }

    #[Test]
    public function admin_sees_categories_with_their_product_counts(): void
    {
        $books = Category::factory()->create(['name' => 'Books']);
        Product::factory()->count(3)->for($books)->create();

        $this->actingAs($this->admin)->get('/categories')
            ->assertOk()
            ->assertSee('Books')
            ->assertSee('3');
    }

    #[Test]
    public function admin_can_create_and_rename_a_category(): void
    {
        $this->actingAs($this->admin)->post('/categories', ['name' => 'Garden'])
            ->assertRedirect(route('categories.index'));

        $category = Category::where('name', 'Garden')->firstOrFail();

        $this->actingAs($this->admin)->put("/categories/{$category->id}", ['name' => 'Garden & Outdoor'])
            ->assertRedirect(route('categories.index'));

        $this->assertSame('Garden & Outdoor', $category->fresh()->name);
    }

    #[Test]
    public function duplicate_names_are_rejected(): void
    {
        Category::factory()->create(['name' => 'Garden']);

        $this->actingAs($this->admin)->from('/categories/create')
            ->post('/categories', ['name' => 'Garden'])
            ->assertRedirect('/categories/create')
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function deleting_a_category_with_products_shows_an_error(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();

        $this->actingAs($this->admin)->from('/categories')
            ->delete("/categories/{$category->id}")
            ->assertRedirect('/categories')
            ->assertSessionHas('error');

        $this->assertModelExists($category);
    }

    #[Test]
    public function an_empty_category_can_be_deleted(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)->delete("/categories/{$category->id}")
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($category);
    }
}
