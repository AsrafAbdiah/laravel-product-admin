<?php

namespace Tests\Feature;

use App\Http\Middleware\SetCurrentTeam;
use App\Models\Team;
use App\Models\User;
use App\Support\CurrentTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SetCurrentTeamTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Run the middleware for a request made by $user and return the team
     * it decided on.
     */
    private function runMiddleware(?User $user): ?Team
    {
        app(CurrentTeam::class)->set(null);

        $request = Request::create('/dashboard');
        $request->setUserResolver(fn () => $user);

        app(SetCurrentTeam::class)->handle($request, fn () => new Response('ok'));

        return app(CurrentTeam::class)->get();
    }

    #[Test]
    public function it_uses_the_users_current_team(): void
    {
        $user = User::factory()->create();
        [$first, $second] = Team::factory()->count(2)->create();
        $user->teams()->attach([$first->id, $second->id]);
        $user->switchTeam($second);

        $this->assertTrue($this->runMiddleware($user)->is($second));
    }

    #[Test]
    public function it_picks_a_team_when_the_user_has_none_selected(): void
    {
        $user = User::factory()->create(['current_team_id' => null]);
        $user->teams()->detach();
        $team = Team::factory()->create();
        $user->teams()->attach($team);

        $this->assertTrue($this->runMiddleware($user->fresh())->is($team));
        $this->assertSame($team->id, $user->fresh()->current_team_id);
    }

    #[Test]
    public function it_moves_the_user_out_of_a_team_they_were_removed_from(): void
    {
        $user = User::factory()->create();
        $user->teams()->detach();
        [$kept, $removed] = Team::factory()->count(2)->create();
        $user->teams()->attach([$kept->id, $removed->id]);
        $user->switchTeam($removed);

        $user->teams()->detach($removed);

        $this->assertTrue($this->runMiddleware($user->fresh())->is($kept));
    }

    #[Test]
    public function guests_have_no_current_team(): void
    {
        $this->assertNull($this->runMiddleware(null));
    }
}
