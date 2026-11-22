<?php

namespace Tests\Unit;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_team_gets_a_unique_slug_from_its_name(): void
    {
        $first = Team::create(['name' => 'Kedai Ali']);
        $second = Team::create(['name' => 'Kedai Ali']);

        $this->assertStringStartsWith('kedai-ali-', $first->slug);
        $this->assertNotSame($first->slug, $second->slug);
        $this->assertSame('free', $first->fresh()->plan);
    }

    #[Test]
    public function a_user_can_belong_to_several_teams(): void
    {
        $user = User::factory()->create();
        $teams = Team::factory()->count(2)->create();

        $user->teams()->attach($teams);

        $this->assertCount(2, $user->teams);
        $this->assertTrue($teams->first()->users->contains($user));
    }

    #[Test]
    public function a_user_can_switch_to_a_team_they_belong_to(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $user->teams()->attach($team);

        $user->switchTeam($team);

        $this->assertTrue($user->fresh()->currentTeam->is($team));
    }

    #[Test]
    public function a_user_cannot_switch_to_someone_elses_team(): void
    {
        $user = User::factory()->create();
        $otherTeam = Team::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        $user->switchTeam($otherTeam);
    }
}
