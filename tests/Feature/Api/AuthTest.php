<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_log_in_and_receive_a_token(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'secret123']);

        $this->postJson('/api/login', ['email' => $admin->email, 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'email']])
            ->assertJsonPath('token_type', 'Bearer');

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    #[Test]
    public function login_fails_with_wrong_password(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'secret123']);

        $this->postJson('/api/login', ['email' => $admin->email, 'password' => 'wrong-password'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function login_requires_email_and_password(): void
    {
        $this->postJson('/api/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    #[Test]
    public function non_admin_cannot_get_a_token(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertForbidden();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function guest_cannot_access_protected_endpoints(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
        $this->getJson('/api/me')->assertUnauthorized();
    }

    #[Test]
    public function non_admin_with_a_token_is_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/products')->assertForbidden();
    }

    #[Test]
    public function admin_can_view_profile_and_log_out(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('email', $admin->email);

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function login_is_rate_limited(): void
    {
        $admin = User::factory()->admin()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => $admin->email, 'password' => 'wrong']);
        }

        $this->postJson('/api/login', ['email' => $admin->email, 'password' => 'wrong'])
            ->assertTooManyRequests();
    }
}
