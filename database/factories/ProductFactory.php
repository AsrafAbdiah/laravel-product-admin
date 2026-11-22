<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            // A product always lives in the same team as its category.
            'team_id' => fn (array $attributes) => Category::withoutGlobalScopes()->find($attributes['category_id'])?->team_id,
            'name' => ucfirst(fake()->words(3, true)),
            'description' => fake()->sentence(12),
            'price' => fake()->randomFloat(2, 5, 2000),
            'stock' => fake()->numberBetween(0, 500),
            'enabled' => fake()->boolean(80),
        ];
    }

    /**
     * Indicate that the product is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }
}
