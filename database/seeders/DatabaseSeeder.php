<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Demo admin account (documented in the README, for local use only).
        User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        // A normal user, handy for checking that non-admins are blocked.
        User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $categories = collect(['Electronics', 'Books', 'Clothing', 'Home & Kitchen', 'Sports'])
            ->map(fn (string $name) => Category::create(['name' => $name]));

        foreach ($categories as $category) {
            Product::factory()->count(10)->for($category)->create();
        }
    }
}
