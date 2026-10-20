<?php

namespace Tests\Feature\Web;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductWebTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_is_redirected_to_login(): void
    {
        $this->get('/products')->assertRedirect('/login');
    }

    #[Test]
    public function admin_can_log_in_through_the_form(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'secret123']);

        $this->post('/login', ['email' => $admin->email, 'password' => 'secret123'])
            ->assertRedirect(route('products.index'));

        $this->assertAuthenticatedAs($admin);
    }

    #[Test]
    public function non_admin_cannot_log_in_to_the_dashboard(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function admin_can_view_and_filter_the_product_list(): void
    {
        $books = Category::factory()->create(['name' => 'Books']);
        Product::factory()->for($books)->create(['name' => 'Clean Code']);
        Product::factory()->create(['name' => 'Gaming Chair']);

        $this->actingAs(User::factory()->admin()->create())
            ->get("/products?category_id={$books->id}")
            ->assertOk()
            ->assertSee('Clean Code')
            ->assertDontSee('Gaming Chair');
    }

    #[Test]
    public function admin_can_create_a_product_from_the_form(): void
    {
        $category = Category::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post('/products', [
                'category_id' => $category->id,
                'name' => 'Standing Desk',
                'price' => '899.00',
                'stock' => 4,
                'enabled' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['name' => 'Standing Desk', 'enabled' => false]);
    }

    #[Test]
    public function form_validation_errors_are_shown(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->from('/products/create')
            ->post('/products', ['name' => ''])
            ->assertRedirect('/products/create')
            ->assertSessionHasErrors(['name', 'category_id', 'price', 'stock']);
    }

    #[Test]
    public function admin_can_view_edit_and_delete_a_product(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $this->actingAs($admin)->get("/products/{$product->id}")->assertOk()->assertSee($product->name);
        $this->actingAs($admin)->get("/products/{$product->id}/edit")->assertOk();

        $this->actingAs($admin)->put("/products/{$product->id}", ['name' => 'Renamed'])->assertRedirect();
        $this->assertSame('Renamed', $product->fresh()->name);

        $this->actingAs($admin)->delete("/products/{$product->id}")->assertRedirect(route('products.index'));
        $this->assertSoftDeleted($product);
    }

    #[Test]
    public function admin_can_bulk_delete_from_the_dashboard(): void
    {
        $products = Product::factory()->count(3)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete('/products/bulk', ['ids' => $products->pluck('id')->all()])
            ->assertRedirect()
            ->assertSessionHas('success', '3 product(s) deleted.');

        $this->assertSame(0, Product::count());
    }

    #[Test]
    public function non_admin_session_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())->get('/products')->assertForbidden();
    }
}
