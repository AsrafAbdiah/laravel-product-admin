<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a token through the settings page and return its plain text.
     */
    private function createToken(User $user, string $access): string
    {
        $this->actingAs($user)
            ->post('/settings/tokens', ['name' => "My {$access} token", 'access' => $access])
            ->assertRedirect(route('settings.tokens'))
            ->assertSessionHas('plainToken');

        return session('plainToken');
    }

    private function newProduct(): array
    {
        return ['category_id' => Category::factory()->create()->id, 'name' => 'Via API', 'price' => 5, 'stock' => 1];
    }

    #[Test]
    public function a_new_token_is_shown_once_with_the_chosen_access(): void
    {
        $user = User::factory()->admin()->create();
        $plain = $this->createToken($user, 'read');

        $token = PersonalAccessToken::findToken($plain);
        $this->assertSame(['read'], $token->abilities);
        $this->assertTrue($token->tokenable->is($user));

        // The page right after creating it shows the token, the next visit doesn't.
        $this->get('/settings/tokens')->assertOk()->assertSee($plain);
        $this->get('/settings/tokens')->assertOk()->assertSee('My read token')->assertDontSee($plain);
    }

    #[Test]
    public function a_read_only_token_can_read_but_never_write(): void
    {
        $owner = User::factory()->admin()->create();
        $plain = $this->createToken($owner, 'read');
        auth()->forgetGuards();

        $this->withToken($plain)->getJson('/api/products')->assertOk();
        $this->withToken($plain)->postJson('/api/products', $this->newProduct())
            ->assertForbidden()
            ->assertJsonPath('message', 'This API token is not allowed to write.');
    }

    #[Test]
    public function a_write_token_can_change_data(): void
    {
        $owner = User::factory()->admin()->create();
        $plain = $this->createToken($owner, 'write');
        auth()->forgetGuards();

        $this->withToken($plain)->postJson('/api/products', $this->newProduct())->assertCreated();
    }

    #[Test]
    public function a_token_never_allows_more_than_the_users_role(): void
    {
        $viewer = User::factory()->withRole(Roles::VIEWER)->create();
        $plain = $this->createToken($viewer, 'write');
        auth()->forgetGuards();

        $this->withToken($plain)->postJson('/api/products', $this->newProduct())->assertForbidden();
        $this->assertSame(0, Product::count());
    }

    #[Test]
    public function a_read_only_token_can_still_log_itself_out(): void
    {
        $plain = $this->createToken(User::factory()->admin()->create(), 'read');
        auth()->forgetGuards();

        $this->withToken($plain)->postJson('/api/logout')->assertOk();
        $this->assertNull(PersonalAccessToken::findToken($plain));
    }

    #[Test]
    public function a_revoked_token_stops_working(): void
    {
        $user = User::factory()->admin()->create();
        $plain = $this->createToken($user, 'write');
        $token = PersonalAccessToken::findToken($plain);

        $this->actingAs($user)->delete("/settings/tokens/{$token->id}")->assertSessionHas('success');
        auth()->forgetGuards();

        $this->withToken($plain)->getJson('/api/products')->assertUnauthorized();
    }

    #[Test]
    public function you_cannot_revoke_someone_elses_token(): void
    {
        $theirToken = User::factory()->admin()->create()->createToken('theirs');

        $this->actingAs(User::factory()->admin()->create())
            ->delete("/settings/tokens/{$theirToken->accessToken->id}")
            ->assertNotFound();

        $this->assertModelExists($theirToken->accessToken);
    }
}
