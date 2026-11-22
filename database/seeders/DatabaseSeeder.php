<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use App\Support\CurrentTeam;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

/**
 * Demo data: realistic products, a couple of weeks of stock history
 * and a few items in the trash, so every page has something to show.
 *
 * Model events stay on (no WithoutModelEvents) because the stock
 * history is written by ProductObserver.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * name, price (RM), opening stock, description
     *
     * @var array<string, array<int, array{0: string, 1: float, 2: int, 3: string}>>
     */
    private const CATALOGUE = [
        'Electronics' => [
            ['Wireless Mouse', 49.90, 120, 'Ergonomic 2.4GHz mouse with silent clicks.'],
            ['Mechanical Keyboard', 289.00, 35, 'Hot-swappable switches, white backlight.'],
            ['USB-C Hub 7-in-1', 89.90, 60, 'HDMI 4K, 3x USB-A, SD and microSD readers, 100W pass-through.'],
            ['27" IPS Monitor', 899.00, 8, 'QHD resolution, 75Hz, height-adjustable stand.'],
            ['Noise-Cancelling Headphones', 649.00, 14, 'Over-ear, 30-hour battery life.'],
            ['Portable SSD 1TB', 399.00, 0, 'USB 3.2, up to 1050MB/s.'],
            ['Webcam 1080p', 159.00, 42, 'Auto light correction and dual microphones.'],
        ],
        'Books' => [
            ['Clean Code', 159.90, 25, 'A handbook of agile software craftsmanship.'],
            ['The Pragmatic Programmer', 179.90, 18, '20th anniversary edition.'],
            ['Designing Data-Intensive Applications', 219.00, 6, 'The big ideas behind reliable, scalable systems.'],
            ['Laravel: Up & Running', 199.00, 12, 'A framework for building modern PHP apps.'],
            ['Atomic Habits', 89.90, 70, 'Small changes, remarkable results.'],
            ['Sapiens', 75.00, 3, 'A brief history of humankind.'],
        ],
        'Clothing' => [
            ['Cotton Crew Tee', 39.90, 200, 'Heavyweight cotton, regular fit.'],
            ['Denim Jacket', 189.00, 22, 'Classic trucker style, stonewashed.'],
            ['Running Shorts', 59.90, 9, 'Lightweight with a zip pocket.'],
            ['Wool Beanie', 45.00, 0, 'Merino blend, one size.'],
            ['Rain Jacket', 239.00, 17, 'Waterproof, packable hood.'],
            ['Chino Trousers', 129.00, 40, 'Stretch cotton, slim fit.'],
        ],
        'Home & Kitchen' => [
            ['Pour-Over Coffee Set', 129.00, 15, 'Glass dripper, carafe and 100 paper filters.'],
            ['Cast Iron Skillet 26cm', 149.00, 28, 'Pre-seasoned, oven safe.'],
            ['Chef\'s Knife 20cm', 219.00, 7, 'German stainless steel, full tang.'],
            ['Bamboo Cutting Board', 55.00, 64, 'Large, with a juice groove.'],
            ['Electric Kettle 1.7L', 99.00, 33, 'Auto shut-off, 2200W.'],
            ['Linen Table Runner', 49.00, 10, 'Natural linen, 180cm.'],
        ],
        'Sports' => [
            ['Yoga Mat 6mm', 89.00, 45, 'Non-slip, with carry strap.'],
            ['Adjustable Dumbbells 2x10kg', 329.00, 5, 'Quick-change plates and storage tray.'],
            ['Badminton Racket', 259.00, 26, 'Graphite frame, 83g.'],
            ['Shuttlecocks (12 pack)', 69.00, 90, 'Feather, medium speed.'],
            ['Water Bottle 750ml', 45.00, 150, 'Insulated stainless steel.'],
            ['Resistance Bands Set', 79.00, 2, 'Five levels, with handles and door anchor.'],
        ],
    ];

    /**
     * A second, smaller shop, so team isolation can be demonstrated.
     *
     * @var array<string, array<int, array{0: string, 1: float, 2: int, 3: string}>>
     */
    private const SECOND_SHOP_CATALOGUE = [
        'Coffee' => [
            ['House Blend Beans 500g', 45.00, 40, 'Medium roast, chocolate notes.'],
            ['Single Origin Sabah 250g', 38.00, 6, 'Light roast from Tenom.'],
            ['Cold Brew Bottle 1L', 18.50, 24, 'Ready to drink.'],
        ],
        'Bakery' => [
            ['Butter Croissant', 7.50, 30, 'Baked fresh every morning.'],
            ['Pandan Chiffon Cake', 32.00, 4, 'Whole cake, serves 8.'],
        ],
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // One member per role in the demo store, so each role can be tried out.
        $demoShop = Team::create(['name' => 'BandarOS Demo Store']);
        $this->seedShop($demoShop, [
            ['Admin', 'admin@example.com', Roles::OWNER],
            ['Aina (Manager)', 'manager@example.com', Roles::ADMIN],
            ['Staff Member', 'staff@example.com', Roles::STAFF],
            ['Regular User', 'user@example.com', Roles::VIEWER],
        ], self::CATALOGUE, withHistory: true);

        $secondShop = Team::create(['name' => 'Kedai Kopi Siti']);
        $this->seedShop($secondShop, [
            ['Siti', 'siti@example.com', Roles::OWNER],
        ], self::SECOND_SHOP_CATALOGUE, withHistory: false);

        app(CurrentTeam::class)->set(null);
    }

    /**
     * Create a shop's members and products. Everything inside runs "as" that
     * team, so BelongsToTeam fills in team_id automatically.
     *
     * @param  array<int, array{0: string, 1: string, 2: string}>  $members  name, email, role (the first is the owner)
     * @param  array<string, array<int, array{0: string, 1: float, 2: int, 3: string}>>  $catalogue
     */
    private function seedShop(Team $team, array $members, array $catalogue, bool $withHistory): void
    {
        app(CurrentTeam::class)->set($team);

        $users = collect($members)->map(fn (array $member) => User::factory()
            ->withRole($member[2])
            ->create(['name' => $member[0], 'email' => $member[1], 'password' => 'password']));
        $owner = $users->first();

        // Pretend the catalogue was set up two weeks ago by the owner, so the
        // stock history and "x days ago" labels look realistic. $today is
        // captured first because now() returns the frozen time afterwards.
        $today = now()->toImmutable();
        Date::setTestNow($today->subDays(14));
        auth()->setUser($owner);

        $products = collect();
        foreach ($catalogue as $categoryName => $items) {
            $category = Category::create(['name' => $categoryName]);

            foreach ($items as [$name, $price, $stock, $description]) {
                $products[$name] = Product::create([
                    'category_id' => $category->id,
                    'name' => $name,
                    'description' => $description,
                    'price' => $price,
                    'stock' => $stock,
                    'enabled' => true,
                ]);
            }
        }

        if (! $withHistory) {
            Date::setTestNow();
            auth()->forgetUser();

            return;
        }

        // A few products are hidden from the shop.
        foreach (['Linen Table Runner', 'Rain Jacket'] as $name) {
            $products[$name]->update(['enabled' => false]);
        }

        // Stock changes over the following days: sales and restocks.
        $changes = [
            12 => ['Wireless Mouse' => 96, 'Clean Code' => 19],
            10 => ['Portable SSD 1TB' => 12, 'Cotton Crew Tee' => 176],
            8 => ['Mechanical Keyboard' => 29, 'Yoga Mat 6mm' => 38],
            5 => ['Portable SSD 1TB' => 0, 'Atomic Habits' => 55],
            3 => ['27" IPS Monitor' => 4, 'Wireless Mouse' => 150],
            1 => ['Sapiens' => 1, 'Running Shorts' => 24, 'Webcam 1080p' => 36],
        ];
        foreach ($changes as $daysAgo => $updates) {
            Date::setTestNow($today->subDays($daysAgo));
            foreach ($updates as $name => $newStock) {
                $products[$name]->update(['stock' => $newStock]);
            }
        }

        // Discontinued items waiting in the trash.
        Date::setTestNow($today->subDays(2));
        foreach (['Wool Beanie', 'Linen Table Runner'] as $name) {
            $products[$name]->delete();
        }

        Date::setTestNow();
        auth()->forgetUser();
    }
}
