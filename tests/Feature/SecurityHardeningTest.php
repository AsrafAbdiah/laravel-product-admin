<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Vite;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function pages_send_security_headers_and_a_strict_csp(): void
    {
        $response = $this->get('/login')->assertOk();

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);

        // The inline theme script carries this request's nonce, so it is allowed to run.
        $nonce = Vite::cspNonce();
        $this->assertStringContainsString("'nonce-{$nonce}'", $csp);
        $response->assertSee('<script nonce="'.$nonce.'">', false);
    }

    #[Test]
    public function api_responses_get_the_basic_headers_but_no_csp(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeaderMissing('Content-Security-Policy');
    }

    #[Test]
    public function the_api_is_rate_limited_per_team(): void
    {
        config(['bandaros.api_rate_limit' => 3]);

        $member = User::factory()->admin()->create();
        $teammate = User::factory()->admin()->create();
        $otherShop = User::factory()->admin()->create(['current_team_id' => Team::factory()->create()->id]);
        $otherShop->teams()->attach($otherShop->current_team_id);
        $otherShop->setTeamRole($otherShop->currentTeam, 'owner');

        Sanctum::actingAs($member, ['*']);
        $this->getJson('/api/me')->assertOk();
        $this->getJson('/api/me')->assertOk();

        // The limit is shared by the whole team...
        Sanctum::actingAs($teammate, ['*']);
        $this->getJson('/api/me')->assertOk();
        $this->getJson('/api/me')->assertTooManyRequests();

        // ...but another shop is not affected.
        Sanctum::actingAs($otherShop, ['*']);
        $this->getJson('/api/me')->assertOk();
    }

    #[Test]
    public function the_sessions_page_lists_this_users_sessions(): void
    {
        $user = User::factory()->create();
        DB::table('sessions')->insert([
            ['id' => 'phone-session', 'user_id' => $user->id, 'ip_address' => '10.0.0.7', 'payload' => '', 'last_activity' => now()->subHour()->timestamp,
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1'],
            ['id' => 'someone-else', 'user_id' => User::factory()->create()->id, 'ip_address' => '10.0.0.9', 'payload' => '', 'last_activity' => now()->timestamp,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0'],
        ]);

        $this->actingAs($user)->get('/settings/sessions')
            ->assertOk()
            ->assertSee('Safari on iOS')
            ->assertSee('10.0.0.7')
            ->assertDontSee('10.0.0.9');
    }

    #[Test]
    public function other_sessions_can_be_logged_out_with_the_password(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);
        $otherUser = User::factory()->create();
        DB::table('sessions')->insert([
            ['id' => 'old-laptop', 'user_id' => $user->id, 'ip_address' => null, 'user_agent' => null, 'payload' => '', 'last_activity' => now()->timestamp],
            ['id' => 'not-mine', 'user_id' => $otherUser->id, 'ip_address' => null, 'user_agent' => null, 'payload' => '', 'last_activity' => now()->timestamp],
        ]);

        $this->actingAs($user)->from('/settings/sessions')
            ->delete('/settings/sessions', ['password' => 'wrong'])
            ->assertSessionHasErrorsIn('logoutOtherSessions', 'password');
        $this->assertDatabaseHas('sessions', ['id' => 'old-laptop']);

        $this->actingAs($user)->from('/settings/sessions')
            ->delete('/settings/sessions', ['password' => 'secret123'])
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('sessions', ['id' => 'old-laptop']);
        $this->assertDatabaseHas('sessions', ['id' => 'not-mine']);
    }
}
