<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->admin()->create(['name' => 'Owner Ali']);
    }

    #[Test]
    public function every_member_can_see_the_team_but_only_managers_get_the_tools(): void
    {
        $viewer = User::factory()->withRole(Roles::VIEWER)->create(['name' => 'Viewer Vee']);

        $this->actingAs($viewer)->get('/team')
            ->assertOk()
            ->assertSee('Owner Ali')
            ->assertSee('Viewer Vee')
            ->assertDontSee('Invite someone');

        $this->actingAs($this->owner)->get('/team')->assertOk()->assertSee('Invite someone');
    }

    #[Test]
    public function an_admin_can_invite_someone_by_email(): void
    {
        Notification::fake();

        $this->actingAs($this->owner)
            ->post('/team/invitations', ['email' => 'New.Person@Example.com', 'role' => Roles::STAFF])
            ->assertSessionHas('success');

        $invitation = TeamInvitation::firstOrFail();
        $this->assertSame('new.person@example.com', $invitation->email);
        $this->assertSame(Roles::STAFF, $invitation->role);
        $this->assertSame($this->defaultTeam->id, $invitation->team_id);

        Notification::assertSentTo(
            new AnonymousNotifiable,
            TeamInvitationNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'new.person@example.com',
        );
    }

    #[Test]
    public function staff_cannot_invite_people(): void
    {
        $this->actingAs(User::factory()->withRole(Roles::STAFF)->create())
            ->post('/team/invitations', ['email' => 'x@example.com', 'role' => Roles::STAFF])
            ->assertForbidden();
    }

    #[Test]
    public function members_and_people_already_invited_cannot_be_invited_again(): void
    {
        Notification::fake();
        $member = User::factory()->withRole(Roles::STAFF)->create();
        $this->actingAs($this->owner)->post('/team/invitations', ['email' => 'pending@example.com', 'role' => Roles::STAFF]);

        $this->actingAs($this->owner)->post('/team/invitations', ['email' => $member->email, 'role' => Roles::STAFF])
            ->assertSessionHasErrors('email');
        $this->actingAs($this->owner)->post('/team/invitations', ['email' => 'pending@example.com', 'role' => Roles::VIEWER])
            ->assertSessionHasErrors('email');
        // "owner" can't be handed out through an invitation.
        $this->actingAs($this->owner)->post('/team/invitations', ['email' => 'boss@example.com', 'role' => Roles::OWNER])
            ->assertSessionHasErrors('role');
    }

    /**
     * Invite $email and return the plain token from the email that was "sent".
     */
    private function inviteAndGetToken(string $email, string $role = Roles::STAFF): string
    {
        Notification::fake();
        $this->actingAs($this->owner)->post('/team/invitations', ['email' => $email, 'role' => $role]);

        $token = null;
        Notification::assertSentTo(new AnonymousNotifiable, TeamInvitationNotification::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        return $token;
    }

    #[Test]
    public function the_invited_person_can_accept_and_joins_with_the_role(): void
    {
        $token = $this->inviteAndGetToken('friend@example.com', Roles::VIEWER);

        // They already run their own shop.
        $otherTeam = Team::factory()->create();
        $friend = User::factory()->create(['email' => 'friend@example.com', 'current_team_id' => null]);
        $friend->teams()->detach();
        $friend->teams()->attach($otherTeam);
        $friend->switchTeam($otherTeam);

        $this->actingAs($friend)->get("/invitations/{$token}")->assertOk()->assertSee('Accept and join');
        $this->actingAs($friend)->post("/invitations/{$token}")->assertRedirect(route('dashboard'));

        $friend->refresh();
        $this->assertTrue($friend->belongsToTeam($this->defaultTeam));
        $this->assertSame(Roles::VIEWER, $friend->teamRole($this->defaultTeam));
        $this->assertSame($this->defaultTeam->id, $friend->current_team_id);
        $this->assertDatabaseCount('team_invitations', 0);
    }

    #[Test]
    public function an_invitation_only_works_for_the_invited_email(): void
    {
        $token = $this->inviteAndGetToken('friend@example.com');
        $someoneElse = User::factory()->create(['email' => 'stranger@example.com']);

        $this->actingAs($someoneElse)->get("/invitations/{$token}")->assertOk()->assertSee('sent to');
        $this->actingAs($someoneElse)->post("/invitations/{$token}")->assertForbidden();
        $this->assertDatabaseCount('team_invitations', 1);
    }

    #[Test]
    public function expired_or_unknown_invitations_are_refused(): void
    {
        $token = $this->inviteAndGetToken('friend@example.com');
        $friend = User::factory()->create(['email' => 'friend@example.com']);

        $this->actingAs($friend)->get('/invitations/not-a-real-token')->assertNotFound();

        $this->travel(TeamInvitation::VALID_FOR_DAYS + 1)->days();
        $this->actingAs($friend)->post("/invitations/{$token}")->assertStatus(410);
    }

    #[Test]
    public function guests_are_asked_to_log_in_first(): void
    {
        $token = $this->inviteAndGetToken('friend@example.com');
        auth()->logout();

        $this->get("/invitations/{$token}")->assertRedirect(route('login'));
    }

    #[Test]
    public function an_admin_can_change_a_members_role_and_remove_them(): void
    {
        $staff = User::factory()->withRole(Roles::STAFF)->create();

        $this->actingAs($this->owner)->patch("/team/members/{$staff->id}", ['role' => Roles::VIEWER])->assertSessionHas('success');
        $this->assertSame(Roles::VIEWER, $staff->teamRole($this->defaultTeam));

        $this->actingAs($this->owner)->delete("/team/members/{$staff->id}")->assertSessionHas('success');
        $staff->refresh();
        $this->assertFalse($staff->belongsToTeam($this->defaultTeam));
        $this->assertNull($staff->teamRole($this->defaultTeam));
    }

    #[Test]
    public function the_owner_and_yourself_are_protected(): void
    {
        $admin = User::factory()->withRole(Roles::ADMIN)->create();

        $this->actingAs($admin)->patch("/team/members/{$this->owner->id}", ['role' => Roles::VIEWER])->assertForbidden();
        $this->actingAs($admin)->delete("/team/members/{$this->owner->id}")->assertForbidden();
        $this->actingAs($admin)->patch("/team/members/{$admin->id}", ['role' => Roles::VIEWER])->assertForbidden();

        $this->assertSame(Roles::OWNER, $this->owner->teamRole($this->defaultTeam));
    }

    #[Test]
    public function members_of_other_teams_cannot_be_touched(): void
    {
        $otherTeam = Team::factory()->create();
        $outsider = User::factory()->create(['current_team_id' => null]);
        $outsider->teams()->detach();
        $outsider->teams()->attach($otherTeam);

        $this->actingAs($this->owner)->patch("/team/members/{$outsider->id}", ['role' => Roles::VIEWER])->assertNotFound();
        $this->actingAs($this->owner)->delete("/team/members/{$outsider->id}")->assertNotFound();
    }

    #[Test]
    public function a_member_of_several_teams_can_switch_between_them(): void
    {
        $secondShop = Team::factory()->create(['name' => 'Second Shop']);
        $this->owner->teams()->attach($secondShop);
        $this->owner->setTeamRole($secondShop, Roles::OWNER);
        $strangersShop = Team::factory()->create();

        $this->actingAs($this->owner)->post("/teams/{$secondShop->id}/switch")->assertRedirect(route('dashboard'));
        $this->assertSame($secondShop->id, $this->owner->fresh()->current_team_id);

        $this->actingAs($this->owner)->post("/teams/{$strangersShop->id}/switch")->assertNotFound();
    }

    #[Test]
    public function only_managers_can_rename_the_shop(): void
    {
        $this->actingAs(User::factory()->withRole(Roles::STAFF)->create())->put('/team', ['name' => 'Hacked'])->assertForbidden();

        $this->actingAs($this->owner)->put('/team', ['name' => 'Kedai Baru'])->assertSessionHas('success');
        $this->assertSame('Kedai Baru', $this->defaultTeam->fresh()->name);
    }
}
