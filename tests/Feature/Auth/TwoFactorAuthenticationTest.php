<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The current 6-digit code for the user's secret, as an authenticator app would show it.
     */
    private function currentCode(User $user): string
    {
        return app(Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));
    }

    #[Test]
    public function turning_it_on_requires_a_recent_password_confirmation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/user/two-factor-authentication')->assertRedirect(route('password.confirm'));
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    #[Test]
    public function a_user_can_turn_it_on_by_confirming_a_code(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);

        $this->post('/user/two-factor-authentication')->assertSessionHas('status', 'two-factor-authentication-enabled');
        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());

        // A wrong code is rejected; the right one switches it on.
        $this->post('/user/confirmed-two-factor-authentication', ['code' => '000000'])
            ->assertSessionHasErrorsIn('confirmTwoFactorAuthentication', 'code');
        $this->post('/user/confirmed-two-factor-authentication', ['code' => $this->currentCode($user)])
            ->assertSessionHas('status', 'two-factor-authentication-confirmed');

        $this->assertTrue($user->fresh()->hasEnabledTwoFactorAuthentication());
        $this->get('/settings/profile')->assertOk()->assertSee('Save these recovery codes');
    }

    /**
     * Turn two-factor on for the user, log out, and log back in with the
     * password, which should stop at the two-factor challenge.
     */
    private function enableTwoFactorAndStartLogin(User $user): string
    {
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
        $this->post('/user/two-factor-authentication');
        $usedCode = $this->currentCode($user->fresh());
        $this->post('/user/confirmed-two-factor-authentication', ['code' => $usedCode]);
        $this->post('/logout');

        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect(route('two-factor.login'));
        $this->assertGuest();

        return $usedCode;
    }

    #[Test]
    public function logging_in_with_two_factor_on_asks_for_a_second_step(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);
        $this->enableTwoFactorAndStartLogin($user);

        $this->get('/two-factor-challenge')->assertOk()->assertSee('Two-factor authentication');

        // A recovery code works once, then it is used up.
        $recoveryCode = $user->fresh()->recoveryCodes()[0];
        $this->post('/two-factor-challenge', ['recovery_code' => $recoveryCode])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotContains($recoveryCode, $user->fresh()->recoveryCodes());
    }

    #[Test]
    public function a_code_that_was_already_used_cannot_be_used_again(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        // The code used to confirm the setup is still "current", but it has been used.
        $usedCode = $this->enableTwoFactorAndStartLogin($user);

        $this->post('/two-factor-challenge', ['code' => $usedCode])->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
    }
}
