<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Category;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use App\Support\CurrentTeam;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->admin()->create(['name' => 'Owner Ali']);
    }

    #[Test]
    public function product_changes_are_logged_with_who_and_what_changed(): void
    {
        Sanctum::actingAs($this->owner, ['*']);
        $category = Category::factory()->create();

        $id = $this->postJson('/api/products', ['category_id' => $category->id, 'name' => 'Desk Lamp', 'price' => 50, 'stock' => 5])->json('data.id');
        $this->putJson("/api/products/{$id}", ['price' => 45, 'stock' => 9])->assertOk();
        $this->deleteJson("/api/products/{$id}")->assertOk();

        $log = Activity::where('log_name', 'products')->orderBy('id')->get();
        $this->assertSame(['created', 'updated', 'deleted'], $log->pluck('event')->all());
        $this->assertTrue($log->every(fn (Activity $a) => $a->causer_id === $this->owner->id && $a->team_id === $this->defaultTeam->id));

        $update = $log[1];
        $this->assertSame('updated product "Desk Lamp"', $update->description);
        $this->assertEquals(['price' => ['50.00', '45.00'], 'stock' => [5, 9]], $update->fieldChanges());
        $this->assertSame('moved product "Desk Lamp" to the trash', $log[2]->description);
    }

    #[Test]
    public function bulk_actions_and_imports_write_one_summary_entry(): void
    {
        Sanctum::actingAs($this->owner, ['*']);
        $category = Category::factory()->create(['name' => 'Books']);
        $products = Product::factory()->count(3)->for($category)->create();
        Activity::query()->delete();

        $this->deleteJson('/api/products/bulk', ['ids' => $products->pluck('id')->all()])->assertOk();

        $csv = "Name,Category,Price,Stock\nA,Books,1,1\nB,Books,1,1\n";
        $this->withHeader('Accept', 'application/json')
            ->post('/api/products/import', ['file' => UploadedFile::fake()->createWithContent('stock.csv', $csv)])
            ->assertJsonPath('imported', 2);

        $this->assertSame(['bulk_deleted', 'imported'], Activity::orderBy('id')->pluck('event')->all());
        $this->assertSame('imported 2 products from stock.csv', Activity::where('event', 'imported')->value('description'));
    }

    #[Test]
    public function team_changes_are_logged(): void
    {
        Notification::fake();
        $staff = User::factory()->withRole(Roles::STAFF)->create(['name' => 'Staff Sam']);

        $this->actingAs($this->owner)->post('/team/invitations', ['email' => 'new@example.com', 'role' => Roles::VIEWER]);
        $this->actingAs($this->owner)->patch("/team/members/{$staff->id}", ['role' => Roles::VIEWER]);
        $this->actingAs($this->owner)->put('/team', ['name' => 'Renamed Shop']);

        $descriptions = Activity::where('log_name', 'team')->orderBy('id')->pluck('description')->all();
        $this->assertSame([
            'invited new@example.com as viewer',
            "changed Staff Sam's role from staff to viewer",
            'updated the shop settings',
        ], $descriptions);
    }

    #[Test]
    public function only_managers_can_read_the_log_and_only_their_teams(): void
    {
        Category::factory()->create(['name' => 'My Category']);

        $otherTeam = Team::factory()->create();
        app(CurrentTeam::class)->set($otherTeam);
        Category::factory()->create(['name' => 'Secret Category']);
        app(CurrentTeam::class)->set($this->defaultTeam);

        $this->actingAs($this->owner)->get('/activity')
            ->assertOk()
            ->assertSee('created category &quot;My Category&quot;', false)
            ->assertDontSee('Secret Category');

        $this->actingAs(User::factory()->withRole(Roles::STAFF)->create())->get('/activity')->assertForbidden();
    }
}
