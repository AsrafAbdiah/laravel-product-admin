<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use App\Support\CurrentTeam;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Every user starts in a team: the current team when one is set (tests
     * set a default team), otherwise a new team of their own.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if ($user->current_team_id) {
                return;
            }

            $team = app(CurrentTeam::class)->get() ?? Team::create(['name' => $user->name."'s Shop"]);

            $user->teams()->syncWithoutDetaching([$team->id]);
            $user->forceFill(['current_team_id' => $team->id])->save();
        });
    }

    /**
     * Owner of their (current) team: can do everything.
     */
    public function admin(): static
    {
        return $this->withRole(Roles::OWNER);
    }

    /**
     * Give the user a role in their current team.
     */
    public function withRole(string $role): static
    {
        return $this->afterCreating(function (User $user) use ($role) {
            $user->setTeamRole($user->currentTeam, $role);
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
