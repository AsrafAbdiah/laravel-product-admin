<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StockHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        Sanctum::actingAs($this->admin);
    }

    #[Test]
    public function creating_a_product_records_its_opening_stock(): void
    {
        $this->postJson('/api/products', [
            'category_id' => Category::factory()->create()->id,
            'name' => 'Desk Fan',
            'price' => 79,
            'stock' => 25,
        ])->assertCreated();

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => Product::firstOrFail()->id,
            'user_id' => $this->admin->id,
            'old_stock' => 0,
            'new_stock' => 25,
            'change' => 25,
            'reason' => StockMovement::REASON_CREATED,
        ]);
    }

    #[Test]
    public function changing_stock_records_the_old_and_new_values(): void
    {
        $product = Product::factory()->create(['stock' => 40]);

        $this->putJson("/api/products/{$product->id}", ['stock' => 32])->assertOk();

        $movement = $product->stockMovements()->latest('id')->firstOrFail();
        $this->assertSame(40, $movement->old_stock);
        $this->assertSame(32, $movement->new_stock);
        $this->assertSame(-8, $movement->change);
        $this->assertSame(StockMovement::REASON_UPDATED, $movement->reason);
        $this->assertSame($this->admin->id, $movement->user_id);
    }

    #[Test]
    public function editing_other_fields_does_not_record_a_movement(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $before = $product->stockMovements()->count();

        $this->putJson("/api/products/{$product->id}", ['name' => 'New Name', 'price' => 12.5])->assertOk();
        // Sending the same stock value is not a change either.
        $this->putJson("/api/products/{$product->id}", ['stock' => 10])->assertOk();

        $this->assertSame($before, $product->stockMovements()->count());
    }

    #[Test]
    public function history_endpoint_lists_changes_newest_first_with_the_user(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $this->putJson("/api/products/{$product->id}", ['stock' => 9])->assertOk();
        $this->putJson("/api/products/{$product->id}", ['stock' => 3])->assertOk();

        $this->getJson("/api/products/{$product->id}/stock-movements")
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.old_stock', 9)
            ->assertJsonPath('data.0.new_stock', 3)
            ->assertJsonPath('data.0.change', -6)
            ->assertJsonPath('data.0.user.name', $this->admin->name)
            ->assertJsonPath('data.2.reason', 'created');
    }

    #[Test]
    public function permanently_deleting_a_product_removes_its_history(): void
    {
        $product = Product::factory()->create();
        $product->delete();

        $this->deleteJson("/api/products/{$product->id}/force")->assertOk();

        $this->assertDatabaseMissing('stock_movements', ['product_id' => $product->id]);
    }

    #[Test]
    public function product_page_shows_the_stock_history(): void
    {
        $product = Product::factory()->create(['stock' => 7]);

        $this->actingAs($this->admin)->put("/products/{$product->id}", ['stock' => 12])->assertRedirect();

        $this->actingAs($this->admin)->get("/products/{$product->id}")
            ->assertOk()
            ->assertSee('Stock history')
            ->assertSee('7 &rarr; 12', false)
            ->assertSee('+5')
            ->assertSee($this->admin->name);
    }
}
