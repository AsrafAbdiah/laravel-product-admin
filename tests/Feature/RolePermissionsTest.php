<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The full role × action matrix, through the API. Each row says which roles
 * may perform the action; every other role must get a 403.
 */
class RolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    private const ALL = [Roles::OWNER, Roles::ADMIN, Roles::STAFF, Roles::VIEWER];

    /**
     * action name => [allowed roles, request builder]
     *
     * @return array<string, array{0: array<int, string>}>
     */
    public static function actions(): array
    {
        return [
            'list products' => [self::ALL],
            'view a product' => [self::ALL],
            'export' => [self::ALL],
            'dashboard' => [self::ALL],
            'create a product' => [[Roles::OWNER, Roles::ADMIN, Roles::STAFF]],
            'update a product' => [[Roles::OWNER, Roles::ADMIN, Roles::STAFF]],
            'import' => [[Roles::OWNER, Roles::ADMIN, Roles::STAFF]],
            'delete a product' => [[Roles::OWNER, Roles::ADMIN]],
            'bulk delete' => [[Roles::OWNER, Roles::ADMIN]],
            'view the trash' => [[Roles::OWNER, Roles::ADMIN]],
            'restore a product' => [[Roles::OWNER, Roles::ADMIN]],
            'create a category' => [[Roles::OWNER, Roles::ADMIN]],
            'delete a category' => [[Roles::OWNER, Roles::ADMIN]],
        ];
    }

    private function perform(string $action)
    {
        $product = Product::factory()->create();
        $category = Category::factory()->create();

        if ($action === 'restore a product') {
            $product->delete();
        }

        return match ($action) {
            'list products' => $this->getJson('/api/products'),
            'view a product' => $this->getJson("/api/products/{$product->id}"),
            'export' => $this->get('/api/products/export'),
            'dashboard' => $this->getJson('/api/dashboard'),
            'create a product' => $this->postJson('/api/products', ['category_id' => $category->id, 'name' => 'X', 'price' => 1, 'stock' => 1]),
            'update a product' => $this->putJson("/api/products/{$product->id}", ['name' => 'Y']),
            'import' => $this->withHeader('Accept', 'application/json')->post('/api/products/import', [
                'file' => UploadedFile::fake()->createWithContent('p.csv', "Name,Category,Price,Stock\nZ,{$category->name},1,1\n"),
            ]),
            'delete a product' => $this->deleteJson("/api/products/{$product->id}"),
            'bulk delete' => $this->deleteJson('/api/products/bulk', ['ids' => [$product->id]]),
            'view the trash' => $this->getJson('/api/products/trash'),
            'restore a product' => $this->patchJson("/api/products/{$product->id}/restore"),
            'create a category' => $this->postJson('/api/categories', ['name' => 'New Category '.$product->id]),
            'delete a category' => $this->deleteJson("/api/categories/{$category->id}"),
        };
    }

    #[Test]
    #[DataProvider('actions')]
    public function each_role_can_only_do_what_it_is_allowed_to(array $allowedRoles): void
    {
        $action = $this->dataName();

        foreach (self::ALL as $role) {
            Sanctum::actingAs(User::factory()->withRole($role)->create(), ['*']);

            $status = $this->perform($action)->getStatusCode();

            if (in_array($role, $allowedRoles, true)) {
                $this->assertNotSame(403, $status, "A {$role} should be able to {$action}.");
                $this->assertLessThan(400, $status, "A {$role} should be able to {$action} (got {$status}).");
            } else {
                $this->assertSame(403, $status, "A {$role} must not be able to {$action}.");
            }
        }
    }

    #[Test]
    public function roles_are_per_team(): void
    {
        // Owner of their own shop, but only a viewer in another one.
        $user = User::factory()->admin()->create();
        $otherTeam = Team::factory()->create();
        $user->teams()->attach($otherTeam);
        $user->setTeamRole($otherTeam, Roles::VIEWER);

        $this->assertSame(Roles::OWNER, $user->teamRole($this->defaultTeam));
        $this->assertSame(Roles::VIEWER, $user->teamRole($otherTeam));

        Sanctum::actingAs($user, ['*']);
        $this->postJson('/api/categories', ['name' => 'Mine'])->assertCreated();

        // Same user object, next request in the other team: must not reuse the owner role.
        $user->switchTeam($otherTeam);
        $this->postJson('/api/categories', ['name' => 'Theirs'])->assertForbidden();
    }

    #[Test]
    public function the_dashboard_hides_actions_a_viewer_cannot_take(): void
    {
        $this->actingAs(User::factory()->withRole(Roles::VIEWER)->create());
        Product::factory()->create();

        $this->get('/products')->assertOk()->assertDontSee('New product')->assertDontSee('Delete selected');
        $this->get('/products/trash')->assertForbidden();
    }

    #[Test]
    public function staff_see_editing_tools_but_not_deleting_ones(): void
    {
        $this->actingAs(User::factory()->withRole(Roles::STAFF)->create());
        Product::factory()->create();

        $this->get('/products')->assertOk()->assertSee('New product')->assertDontSee('Delete selected');
    }
}
