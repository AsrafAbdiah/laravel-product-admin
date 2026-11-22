<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_settings_page_is_available_to_any_verified_member(): void
    {
        // Not an admin: account settings are for everyone.
        $user = User::factory()->create();

        $this->actingAs($user)->get('/settings/profile')
            ->assertOk()
            ->assertSee('Account settings')
            ->assertSee('Two-factor authentication');
    }

    #[Test]
    public function the_name_can_be_updated(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $this->actingAs($user)->from('/settings/profile')
            ->put('/user/profile-information', ['name' => 'New Name', 'email' => $user->email])
            ->assertRedirect('/settings/profile')
            ->assertSessionHas('status', 'profile-information-updated');

        $this->assertSame('New Name', $user->fresh()->name);
    }

    #[Test]
    public function changing_the_email_requires_verifying_it_again(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/user/profile-information', ['name' => $user->name, 'email' => 'new@example.com']);

        $this->assertSame('new@example.com', $user->fresh()->email);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    #[Test]
    public function the_password_can_be_changed_with_the_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user)->from('/settings/profile')->put('/user/password', [
            'current_password' => 'wrong-password',
            'password' => 'New-password-123',
            'password_confirmation' => 'New-password-123',
        ])->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->actingAs($user)->from('/settings/profile')->put('/user/password', [
            'current_password' => 'old-password',
            'password' => 'New-password-123',
            'password_confirmation' => 'New-password-123',
        ])->assertSessionHas('status', 'password-updated');

        $this->assertTrue(Hash::check('New-password-123', $user->fresh()->password));
    }
}
