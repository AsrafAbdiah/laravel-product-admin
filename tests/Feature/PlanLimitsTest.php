<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlanLimitsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        // A small Free plan, so the limits are quick to reach.
        config(['bandaros.plans.free.max_products' => 3, 'bandaros.plans.free.max_members' => 2]);
        $this->defaultTeam->update(['plan' => 'free']);

        $this->owner = User::factory()->admin()->create();
    }

    #[Test]
    public function the_free_plan_stops_new_products_at_the_limit(): void
    {
        Sanctum::actingAs($this->owner, ['*']);
        $category = Category::factory()->create();
        Product::factory()->count(3)->for($category)->create();

        $this->postJson('/api/products', ['category_id' => $category->id, 'name' => 'One too many', 'price' => 1, 'stock' => 1])
            ->assertForbidden()
            ->assertJsonPath('message', 'Your Free plan allows up to 3 products. The shop owner can upgrade to Pro on the Team page.');

        $this->actingAs($this->owner)->get('/products')
            ->assertOk()
            ->assertSee('Your Free plan allows up to 3 products')
            ->assertDontSee('New product');
    }

    #[Test]
    public function restoring_from_the_trash_also_respects_the_limit(): void
    {
        Sanctum::actingAs($this->owner, ['*']);
        $trashed = Product::factory()->create();
        $trashed->delete();
        Product::factory()->count(3)->create();

        $this->patchJson("/api/products/{$trashed->id}/restore")->assertForbidden();
        $this->assertSoftDeleted($trashed);
    }

    #[Test]
    public function import_is_a_pro_feature(): void
    {
        Sanctum::actingAs($this->owner, ['*']);

        $this->withHeader('Accept', 'application/json')
            ->post('/api/products/import', ['file' => UploadedFile::fake()->createWithContent('p.csv', "Name,Category,Price,Stock\n")])
            ->assertForbidden()
            ->assertJsonPath('message', 'Importing from a file is part of the Pro plan. The shop owner can upgrade on the Team page.');
    }

    #[Test]
    public function invitations_count_towards_the_member_limit(): void
    {
        Notification::fake();

        // Owner + one invitation = 2 seats, the Free maximum here.
        $this->actingAs($this->owner)->post('/team/invitations', ['email' => 'first@example.com', 'role' => Roles::STAFF])->assertSessionHas('success');
        $this->actingAs($this->owner)->post('/team/invitations', ['email' => 'second@example.com', 'role' => Roles::STAFF])->assertForbidden();
    }

    #[Test]
    public function only_the_owner_can_change_the_plan_and_pro_lifts_the_limits(): void
    {
        $admin = User::factory()->withRole(Roles::ADMIN)->create();
        $this->actingAs($admin)->put('/team/plan', ['plan' => 'pro'])->assertForbidden();

        $this->actingAs($this->owner)->put('/team/plan', ['plan' => 'pro'])->assertSessionHas('success');
        $this->assertSame('pro', $this->defaultTeam->fresh()->plan);

        Product::factory()->count(5)->create();
        Sanctum::actingAs($this->owner, ['*']);
        $this->postJson('/api/products', ['category_id' => Category::factory()->create()->id, 'name' => 'Plenty', 'price' => 1, 'stock' => 1])->assertCreated();
    }

    #[Test]
    public function the_team_page_shows_usage_against_the_plan(): void
    {
        Product::factory()->count(2)->create();

        $this->actingAs($this->owner)->get('/team')
            ->assertOk()
            ->assertSeeInOrder(['Products', '2 / 3'])
            ->assertSee('Switch to Pro');
    }
}
