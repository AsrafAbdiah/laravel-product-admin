<?php

namespace Tests\Feature;

use App\Exports\ProductsExport;
use App\Models\Category;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use App\Support\CurrentTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Proves that one shop (team) can never see or touch another shop's data,
 * through any route. "My" data lives in the default test team; the other
 * shop has its own team, admin, category and products.
 */
class TenancyIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    private Team $otherTeam;

    private Category $otherCategory;

    private Product $otherProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = User::factory()->admin()->create();
        Category::factory()->create(['name' => 'Books']);
        Product::factory()->count(2)->create();

        $this->otherTeam = Team::factory()->create(['name' => 'Other Shop']);
        $this->asTeam($this->otherTeam, function () {
            User::factory()->admin()->create();
            $this->otherCategory = Category::factory()->create(['name' => 'Books']);
            $this->otherProduct = Product::factory()->for($this->otherCategory)->create(['name' => 'Secret Product', 'stock' => 3]);
        });
    }

    /**
     * Run a callback as if the given team were the current team.
     */
    private function asTeam(Team $team, callable $callback): void
    {
        app(CurrentTeam::class)->set($team);
        $callback();
        app(CurrentTeam::class)->set($this->defaultTeam);
    }

    #[Test]
    public function new_rows_get_the_current_team_automatically(): void
    {
        $this->assertSame($this->defaultTeam->id, Category::factory()->create()->team_id);
        $this->assertSame($this->otherTeam->id, $this->otherProduct->team_id);
        $this->assertSame($this->otherTeam->id, $this->otherProduct->stockMovements()->withoutGlobalScopes()->value('team_id'));
    }

    #[Test]
    public function the_product_list_and_dashboard_only_show_my_team(): void
    {
        Sanctum::actingAs($this->me);

        $this->getJson('/api/products')->assertOk()->assertJsonPath('meta.total', 2)->assertJsonMissing(['name' => 'Secret Product']);
        $categoryIds = $this->getJson('/api/categories')->assertOk()->json('data.*.id');
        $this->assertNotEmpty($categoryIds);
        $this->assertNotContains($this->otherCategory->id, $categoryIds);
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('totals.products', 2);
    }

    #[Test]
    public function another_teams_product_is_not_found_on_any_endpoint(): void
    {
        Sanctum::actingAs($this->me);
        $id = $this->otherProduct->id;

        $this->getJson("/api/products/{$id}")->assertNotFound();
        $this->putJson("/api/products/{$id}", ['name' => 'Hacked'])->assertNotFound();
        $this->deleteJson("/api/products/{$id}")->assertNotFound();
        $this->getJson("/api/products/{$id}/stock-movements")->assertNotFound();
        $this->patchJson("/api/products/{$id}/restore")->assertNotFound();
        $this->deleteJson("/api/products/{$id}/force")->assertNotFound();

        $this->assertSame('Secret Product', $this->otherProduct->fresh()->name);
        $this->assertNotSoftDeleted($this->otherProduct);
    }

    #[Test]
    public function another_teams_product_is_not_found_in_the_dashboard_pages(): void
    {
        $this->actingAs($this->me);

        $this->get("/products/{$this->otherProduct->id}")->assertNotFound();
        $this->get("/products/{$this->otherProduct->id}/edit")->assertNotFound();
        $this->get('/products')->assertOk()->assertDontSee('Secret Product');
    }

    #[Test]
    public function another_teams_category_is_not_found(): void
    {
        Sanctum::actingAs($this->me);

        $this->getJson("/api/categories/{$this->otherCategory->id}")->assertNotFound();
        $this->deleteJson("/api/categories/{$this->otherCategory->id}")->assertNotFound();
        $this->assertModelExists($this->otherCategory);
    }

    #[Test]
    public function a_product_cannot_be_put_in_another_teams_category(): void
    {
        Sanctum::actingAs($this->me);

        $this->postJson('/api/products', [
            'category_id' => $this->otherCategory->id,
            'name' => 'Sneaky',
            'price' => 1,
            'stock' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('category_id');

        $this->getJson("/api/products?category_id={$this->otherCategory->id}")->assertStatus(422);
    }

    #[Test]
    public function a_client_cannot_choose_the_team_of_a_new_product(): void
    {
        Sanctum::actingAs($this->me);
        $myCategory = Category::where('name', 'Books')->firstOrFail();

        $this->postJson('/api/products', [
            'category_id' => $myCategory->id,
            'team_id' => $this->otherTeam->id,
            'name' => 'Mine',
            'price' => 1,
            'stock' => 1,
        ])->assertCreated();

        $this->assertSame($this->defaultTeam->id, Product::where('name', 'Mine')->value('team_id'));
    }

    #[Test]
    public function bulk_delete_rejects_another_teams_ids(): void
    {
        Sanctum::actingAs($this->me);

        $this->deleteJson('/api/products/bulk', ['ids' => [$this->otherProduct->id]])->assertStatus(422);

        $this->assertNotSoftDeleted($this->otherProduct);
    }

    #[Test]
    public function category_names_only_need_to_be_unique_within_a_team(): void
    {
        Sanctum::actingAs($this->me);

        // "Books" exists in both teams already; a third "Books" in my team is refused.
        $this->postJson('/api/categories', ['name' => 'Books'])->assertStatus(422);
        // A name only the other team uses is fine for me.
        $this->asTeam($this->otherTeam, fn () => Category::factory()->create(['name' => 'Coffee']));
        $this->postJson('/api/categories', ['name' => 'Coffee'])->assertCreated();
    }

    #[Test]
    public function trash_only_shows_my_teams_deleted_products(): void
    {
        $this->otherProduct->delete();
        Sanctum::actingAs($this->me);

        $this->getJson('/api/products/trash')->assertOk()->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function export_only_contains_my_teams_products(): void
    {
        Excel::fake();
        Sanctum::actingAs($this->me);

        $this->get('/api/products/export')->assertOk();

        Excel::matchByRegex();
        Excel::assertDownloaded('/products-.*\.xlsx/', fn (ProductsExport $export) => $export->query()->count() === 2);
    }

    #[Test]
    public function import_does_not_match_another_teams_categories(): void
    {
        Sanctum::actingAs($this->me);
        $csv = "Name,Category,Description,Price,Stock,Status\nImported,Coffee,,5,5,enabled\n";
        $this->asTeam($this->otherTeam, fn () => Category::factory()->create(['name' => 'Coffee']));

        $this->withHeader('Accept', 'application/json')
            ->post('/api/products/import', ['file' => UploadedFile::fake()->createWithContent('p.csv', $csv)])
            ->assertOk()
            ->assertJsonPath('imported', 0)
            ->assertJsonPath('failed.0.row', 2);
    }

    #[Test]
    public function a_user_without_any_team_is_refused(): void
    {
        $loner = User::factory()->admin()->create();
        $loner->teams()->detach();
        $loner->forceFill(['current_team_id' => null])->save();
        Sanctum::actingAs($loner->fresh());

        $this->getJson('/api/products')->assertForbidden();
    }
}
