<?php

namespace Tests\Feature\Web;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrashWebTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    #[Test]
    public function trash_page_lists_deleted_products_only(): void
    {
        Product::factory()->create(['name' => 'Still Here']);
        Product::factory()->create(['name' => 'Binned Lamp'])->delete();

        $this->actingAs($this->admin)->get('/products/trash')
            ->assertOk()
            ->assertSee('Binned Lamp')
            ->assertDontSee('Still Here');
    }

    #[Test]
    public function nav_shows_how_many_products_are_in_the_trash(): void
    {
        Product::factory()->count(4)->create()->each->delete();

        $this->actingAs($this->admin)->get('/products')
            ->assertOk()
            ->assertSeeInOrder(['Trash', '4']);
    }

    #[Test]
    public function admin_can_restore_and_permanently_delete_from_the_trash(): void
    {
        $restoreMe = Product::factory()->create();
        $restoreMe->delete();
        $removeMe = Product::factory()->create();
        $removeMe->delete();

        $this->actingAs($this->admin)->from('/products/trash')
            ->patch("/products/{$restoreMe->id}/restore")
            ->assertRedirect('/products/trash')
            ->assertSessionHas('success');

        $this->actingAs($this->admin)->from('/products/trash')
            ->delete("/products/{$removeMe->id}/force")
            ->assertRedirect('/products/trash')
            ->assertSessionHas('success');

        $this->assertNotSoftDeleted($restoreMe);
        $this->assertModelMissing($removeMe);
    }

    #[Test]
    public function force_deleting_an_active_product_is_refused_with_a_message(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)->from('/products')
            ->delete("/products/{$product->id}/force")
            ->assertRedirect('/products')
            ->assertSessionHas('error', 'This product is not in the trash.');

        $this->assertModelExists($product);
    }
}
