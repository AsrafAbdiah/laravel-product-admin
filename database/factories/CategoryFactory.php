<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Team;
use App\Support\CurrentTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // The current team when one is set (tests set a default team), otherwise a new team.
            'team_id' => fn () => app(CurrentTeam::class)->id() ?? Team::factory(),
            'name' => ucfirst(fake()->unique()->words(2, true)),
        ];
    }
}
