<?php

namespace Tests\Feature\Auth;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function register(array $overrides = [])
    {
        return $this->post('/register', array_merge([
            'name' => 'Ali',
            'shop_name' => 'Kedai Runcit Ali',
            'email' => 'ali@example.com',
            'password' => 'Secret-pass-123',
            'password_confirmation' => 'Secret-pass-123',
        ], $overrides));
    }

    #[Test]
    public function the_sign_up_page_is_shown(): void
    {
        $this->get('/register')->assertOk()->assertSee('Create your shop');
    }

    #[Test]
    public function signing_up_creates_the_user_and_their_shop(): void
    {
        $this->register()->assertRedirect(route('dashboard'));

        $user = User::where('email', 'ali@example.com')->firstOrFail();
        $team = Team::where('name', 'Kedai Runcit Ali')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->belongsToTeam($team));
        $this->assertSame($team->id, $user->current_team_id);
        $this->assertSame('owner', $user->teamRole($team));
    }

    #[Test]
    public function a_shop_name_and_unique_email_are_required(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->register(['shop_name' => '', 'email' => 'taken@example.com'])
            ->assertSessionHasErrors(['shop_name', 'email']);

        $this->assertGuest();
    }

    #[Test]
    public function new_users_must_verify_their_email_first(): void
    {
        Notification::fake();

        $this->register();
        $user = User::where('email', 'ali@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get('/dashboard')->assertRedirect(route('verification.notice'));
        $this->get(route('verification.notice'))->assertOk()->assertSee('Check your inbox');
    }

    #[Test]
    public function the_verification_link_verifies_the_email(): void
    {
        $this->register();
        $user = User::where('email', 'ali@example.com')->firstOrFail();

        $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->get($link)->assertRedirect();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->get('/dashboard')->assertOk();
    }
}
