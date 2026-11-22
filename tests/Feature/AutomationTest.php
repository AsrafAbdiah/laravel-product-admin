<?php

namespace Tests\Feature;

use App\Jobs\ExportProductsJob;
use App\Jobs\ImportProductsJob;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\ExportReadyNotification;
use App\Notifications\ImportFinishedNotification;
use App\Notifications\LowStockNotification;
use App\Notifications\WeeklySummaryNotification;
use App\Services\ProductService;
use App\Support\CurrentTeam;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AutomationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->admin()->create();
    }

    #[Test]
    public function managers_are_alerted_when_stock_crosses_into_low_stock(): void
    {
        Notification::fake();
        $admin = User::factory()->withRole(Roles::ADMIN)->create();
        $staff = User::factory()->withRole(Roles::STAFF)->create();
        $product = Product::factory()->create(['stock' => 20]);
        Sanctum::actingAs($staff, ['*']);

        $this->putJson("/api/products/{$product->id}", ['stock' => 15])->assertOk();
        Notification::assertNothingSent();

        $this->putJson("/api/products/{$product->id}", ['stock' => 4])->assertOk();
        Notification::assertSentTo([$this->owner, $admin], LowStockNotification::class);
        Notification::assertNotSentTo($staff, LowStockNotification::class);

        // Already low: no second alert for every further change.
        $this->putJson("/api/products/{$product->id}", ['stock' => 2])->assertOk();
        Notification::assertSentToTimes($this->owner, LowStockNotification::class, 1);
    }

    #[Test]
    public function notifications_can_be_read_in_the_app(): void
    {
        $product = Product::factory()->create(['name' => 'Desk Lamp', 'stock' => 20]);
        $product->update(['stock' => 0]);

        $notification = $this->owner->notifications()->firstOrFail();
        $this->actingAs($this->owner)->get('/notifications')->assertOk()->assertSee('Desk Lamp is out of stock');

        $this->actingAs($this->owner)->get("/notifications/{$notification->id}")
            ->assertRedirect(route('products.show', $product, absolute: false));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    #[Test]
    public function big_exports_are_built_in_the_background_and_can_be_downloaded(): void
    {
        config(['bandaros.queue_exports_over_rows' => 2]);
        Queue::fake();
        Product::factory()->count(3)->create();

        $this->actingAs($this->owner)->get('/products/export')->assertRedirect()->assertSessionHas('success');
        Sanctum::actingAs($this->owner, ['*']);
        $this->getJson('/api/products/export')->assertStatus(202);

        Queue::assertPushed(ExportProductsJob::class, 2);

        // Run the job as the queue worker would.
        Storage::fake('local');
        Notification::fake();
        $fileId = (string) Str::uuid();
        app(CurrentTeam::class)->set(null);
        (new ExportProductsJob($this->defaultTeam->id, $this->owner->id, [], $fileId))->handle(app(ProductService::class), app(CurrentTeam::class));
        app(CurrentTeam::class)->set($this->defaultTeam);

        Storage::disk('local')->assertExists(ExportProductsJob::path($this->defaultTeam->id, $fileId));
        Notification::assertSentTo($this->owner, ExportReadyNotification::class, fn ($n) => $n->rows === 3);

        $this->actingAs($this->owner)->get("/exports/{$fileId}")->assertOk()->assertDownload('products-export.xlsx');
        $this->actingAs($this->owner)->get('/exports/'.Str::uuid())->assertNotFound();
    }

    #[Test]
    public function another_team_cannot_download_an_export(): void
    {
        Storage::fake('local');
        $fileId = (string) Str::uuid();
        Storage::disk('local')->put(ExportProductsJob::path($this->defaultTeam->id, $fileId), 'xlsx');

        $outsider = User::factory()->create(['current_team_id' => null]);
        $outsider->teams()->detach();
        $otherTeam = Team::factory()->create();
        $outsider->teams()->attach($otherTeam);
        $outsider->setTeamRole($otherTeam, Roles::OWNER);
        $outsider->switchTeam($otherTeam);

        $this->actingAs($outsider)->get("/exports/{$fileId}")->assertNotFound();
    }

    #[Test]
    public function big_imports_run_in_the_background(): void
    {
        config(['bandaros.queue_imports_over_kb' => 0]);
        Queue::fake();
        Storage::fake('local');
        Category::factory()->create(['name' => 'Books']);
        $csv = "Name,Category,Price,Stock\nNovel,Books,25,10\nBad,Nowhere,1,1\n";

        $this->actingAs($this->owner)
            ->post('/products/import', ['file' => UploadedFile::fake()->createWithContent('stock.csv', $csv)])
            ->assertRedirect(route('products.import'))
            ->assertSessionHas('success');

        $job = null;
        Queue::assertPushed(ImportProductsJob::class, function (ImportProductsJob $pushed) use (&$job) {
            $job = $pushed;

            return true;
        });
        Storage::disk('local')->assertExists($job->path);

        // Run it as the worker would: no request, no current team.
        Notification::fake();
        app(CurrentTeam::class)->set(null);
        $job->handle(app(ProductService::class), app(CurrentTeam::class));

        $novel = Product::withoutGlobalScopes()->where('name', 'Novel')->firstOrFail();
        $this->assertSame($this->defaultTeam->id, $novel->team_id);
        $this->assertSame($this->owner->id, StockMovement::withoutGlobalScopes()->where('product_id', $novel->id)->value('user_id'));
        Storage::disk('local')->assertMissing($job->path);
        Notification::assertSentTo($this->owner, ImportFinishedNotification::class, fn ($n) => $n->result['imported'] === 1 && count($n->result['failed']) === 1);
    }

    #[Test]
    public function the_weekly_summary_goes_to_managers_of_shops_with_products(): void
    {
        Notification::fake();
        Product::factory()->create(['stock' => 3]);
        $admin = User::factory()->withRole(Roles::ADMIN)->create();
        $staff = User::factory()->withRole(Roles::STAFF)->create();

        $emptyShop = Team::factory()->create();
        app(CurrentTeam::class)->set($emptyShop);
        $emptyShopOwner = User::factory()->admin()->create();
        app(CurrentTeam::class)->set($this->defaultTeam);

        $this->artisan('bandaros:weekly-summary')->assertSuccessful();

        Notification::assertSentTo([$this->owner, $admin], WeeklySummaryNotification::class,
            fn ($n) => $n->totals['products'] === 1 && count($n->lowStock) === 1);
        Notification::assertNotSentTo([$staff, $emptyShopOwner], WeeklySummaryNotification::class);
    }

    #[Test]
    public function old_exports_and_long_expired_invitations_are_cleaned_up(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('exports/1/old.xlsx', 'x');
        Storage::disk('local')->put('exports/1/new.xlsx', 'x');
        touch(Storage::disk('local')->path('exports/1/old.xlsx'), now()->subDays(30)->getTimestamp());

        $this->artisan('bandaros:prune-exports')->assertSuccessful();
        Storage::disk('local')->assertMissing('exports/1/old.xlsx');
        Storage::disk('local')->assertExists('exports/1/new.xlsx');

        $old = $this->defaultTeam->invitations()->create(['email' => 'a@example.com', 'role' => 'staff', 'token_hash' => 'a', 'expires_at' => now()->subDays(40)]);
        $recent = $this->defaultTeam->invitations()->create(['email' => 'b@example.com', 'role' => 'staff', 'token_hash' => 'b', 'expires_at' => now()->subDay()]);

        $this->artisan('model:prune', ['--model' => [TeamInvitation::class]])->assertSuccessful();
        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }
}
