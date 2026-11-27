<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\OnboardingChecklist;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OnboardingChecklistTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->admin()->create();
    }

    #[Test]
    public function a_new_shop_sees_the_checklist_with_nothing_done(): void
    {
        $this->actingAs($this->owner)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Get your shop set up')
            ->assertSee('0 of 5 done')
            ->assertSee(route('categories.create'))
            ->assertSee('Hide checklist');
    }

    #[Test]
    public function steps_tick_off_by_themselves_as_the_shop_is_set_up(): void
    {
        Product::factory()->for(Category::factory())->create(['image_path' => 'products/photo.jpg']);
        $this->inviteSomeone();

        $done = array_column($this->steps(), 'done', 'title');

        $this->assertSame([
            'Create a category' => true,
            'Add your first product' => true,
            'Add a product photo' => true,
            'Invite a teammate' => true,
            'Turn on two-factor login' => false,
        ], $done);

        $this->actingAs($this->owner)->get('/dashboard')->assertSee('4 of 5 done');
    }

    #[Test]
    public function a_teammate_who_already_joined_counts_as_invited(): void
    {
        $this->assertFalse($this->steps()[3]['done']);

        User::factory()->withRole(Roles::STAFF)->create();

        $this->assertTrue($this->steps()[3]['done']);
    }

    #[Test]
    public function only_a_product_with_a_photo_ticks_the_photo_step(): void
    {
        Product::factory()->create(['image_path' => null]);

        $steps = $this->steps();
        $this->assertTrue($steps[1]['done']);
        $this->assertFalse($steps[2]['done']);
    }

    #[Test]
    public function the_checklist_goes_away_once_every_step_is_done(): void
    {
        Product::factory()->create(['image_path' => 'products/photo.jpg']);
        $this->inviteSomeone();
        $this->owner->forceFill(['two_factor_secret' => encrypt('secret'), 'two_factor_confirmed_at' => now()])->save();

        $this->actingAs($this->owner)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Get your shop set up');
    }

    #[Test]
    public function a_manager_can_hide_the_checklist_for_the_whole_shop(): void
    {
        $admin = User::factory()->withRole(Roles::ADMIN)->create();

        $this->actingAs($admin)
            ->from('/dashboard')
            ->post('/onboarding/dismiss')
            ->assertRedirect('/dashboard')
            ->assertSessionHas('success');

        $this->assertNotNull($this->defaultTeam->fresh()->onboarding_dismissed_at);

        // fresh(): the in-memory owner still holds the team as it was before the change.
        $this->actingAs($this->owner->fresh())->get('/dashboard')->assertOk()->assertDontSee('Get your shop set up');
    }

    #[Test]
    public function staff_and_viewers_do_not_see_the_checklist_and_cannot_hide_it(): void
    {
        foreach ([Roles::STAFF, Roles::VIEWER] as $role) {
            $member = User::factory()->withRole($role)->create();

            $this->actingAs($member)->get('/dashboard')->assertOk()->assertDontSee('Get your shop set up');
            $this->actingAs($member)->post('/onboarding/dismiss')->assertForbidden();
        }

        $this->assertNull($this->defaultTeam->fresh()->onboarding_dismissed_at);
    }

    /**
     * @return list<array{title: string, description: string, url: string, action: string, done: bool}>
     */
    private function steps(): array
    {
        return app(OnboardingChecklist::class)->steps($this->defaultTeam, $this->owner);
    }

    private function inviteSomeone(): void
    {
        $this->defaultTeam->invitations()->create([
            'email' => 'new.hire@example.com',
            'role' => Roles::STAFF,
            'token_hash' => TeamInvitation::hashToken('some-token'),
            'invited_by' => $this->owner->id,
            'expires_at' => now()->addWeek(),
        ]);
    }
}
