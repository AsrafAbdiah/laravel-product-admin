<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use App\Support\CommandPalette;
use App\Support\CurrentTeam;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Js;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommandPaletteSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->viewer = User::factory()->withRole(Roles::VIEWER)->create();
    }

    #[Test]
    public function it_finds_products_and_categories_by_name(): void
    {
        $drinks = Category::factory()->create(['name' => 'Cold drinks']);
        $tea = Product::factory()->for($drinks)->create(['name' => 'Iced lemon tea', 'stock' => 12]);
        Product::factory()->for($drinks)->create(['name' => 'Mineral water']);

        $this->actingAs($this->viewer)
            ->getJson('/search?q=lemon')
            ->assertOk()
            ->assertExactJson([
                'products' => [[
                    'id' => $tea->id,
                    'name' => 'Iced lemon tea',
                    'category' => 'Cold drinks',
                    'stock' => 12,
                    'url' => route('products.show', $tea),
                ]],
                'categories' => [],
            ]);

        $this->actingAs($this->viewer)
            ->getJson('/search?q=drinks')
            ->assertOk()
            ->assertJsonCount(0, 'products')
            ->assertJsonPath('categories.0.name', 'Cold drinks')
            ->assertJsonPath('categories.0.products_count', 2)
            ->assertJsonPath('categories.0.url', route('products.index', ['category_id' => $drinks->id]));
    }

    #[Test]
    public function it_never_returns_another_shops_records(): void
    {
        Product::factory()->create(['name' => 'Our coffee beans']);

        $otherShop = Team::factory()->create();
        app(CurrentTeam::class)->set($otherShop);
        Category::factory()->create(['name' => 'Coffee']);
        Product::factory()->create(['name' => 'Their coffee beans']);
        app(CurrentTeam::class)->set($this->defaultTeam);

        $this->actingAs($this->viewer)
            ->getJson('/search?q=coffee')
            ->assertOk()
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.name', 'Our coffee beans')
            ->assertJsonCount(0, 'categories');
    }

    #[Test]
    public function wildcards_typed_by_the_user_are_matched_literally(): void
    {
        Product::factory()->create(['name' => 'Gift card']);
        Product::factory()->create(['name' => '50% off voucher']);

        $this->actingAs($this->viewer)
            ->getJson('/search?q=%25')   // "%"
            ->assertOk()
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.name', '50% off voucher');
    }

    #[Test]
    public function an_empty_search_returns_nothing(): void
    {
        Product::factory()->create();

        $this->actingAs($this->viewer)
            ->getJson('/search?q=%20%20')
            ->assertOk()
            ->assertExactJson(['products' => [], 'categories' => []]);
    }

    #[Test]
    public function it_returns_at_most_eight_products(): void
    {
        Product::factory()->count(10)->create(['name' => 'Same name']);

        $this->actingAs($this->viewer)->getJson('/search?q=same')->assertJsonCount(8, 'products');
    }

    #[Test]
    public function guests_and_members_without_a_role_cannot_search(): void
    {
        $this->getJson('/search?q=a')->assertUnauthorized();

        $this->actingAs(User::factory()->create())->getJson('/search?q=a')->assertForbidden();
    }

    #[Test]
    public function the_palette_only_offers_what_the_role_allows(): void
    {
        $palette = app(CommandPalette::class);
        $labels = fn (User $user) => array_column($palette->commands($user, $this->defaultTeam), 'label');

        $owner = User::factory()->admin()->create();
        $this->assertContains('New product', $labels($owner));
        $this->assertContains('Invite a teammate', $labels($owner));
        $this->assertContains('Activity log', $labels($owner));

        $viewerLabels = $labels($this->viewer);
        $this->assertContains('Products', $viewerLabels);
        $this->assertNotContains('New product', $viewerLabels);
        $this->assertNotContains('Invite a teammate', $viewerLabels);
        $this->assertNotContains('Activity log', $viewerLabels);
        $this->assertNotContains('Trash', $viewerLabels);
    }

    #[Test]
    public function every_app_page_includes_the_palette(): void
    {
        $this->actingAs($this->viewer)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('commandPalette(', false)
            ->assertSee(Js::from(route('search'))->toHtml(), false)
            ->assertSee('Search…');
    }
}
