<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Vite;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketingPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function pages(): array
    {
        return [
            'home' => ['/', 'Every shelf counted.'],
            'pricing' => ['/pricing', 'Compare plans'],
            'terms' => ['/terms', 'Terms of Service'],
            'privacy' => ['/privacy', 'Privacy Policy'],
        ];
    }

    #[Test]
    #[DataProvider('pages')]
    public function guests_can_open_every_public_page(string $url, string $heading): void
    {
        $this->get($url)
            ->assertOk()
            ->assertSee($heading)
            ->assertSee('Start free')
            ->assertSee('Log in')
            ->assertDontSee('Go to dashboard');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function urls(): array
    {
        return array_map(fn (array $page) => [$page[0]], self::pages());
    }

    #[Test]
    #[DataProvider('urls')]
    public function public_pages_send_the_strict_csp(string $url): void
    {
        $csp = $this->get($url)->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);
    }

    #[Test]
    public function signed_in_users_see_a_link_to_their_dashboard_instead_of_sign_up(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('Go to dashboard')
            ->assertSee(route('dashboard'))
            ->assertDontSee('Log in');
    }

    #[Test]
    public function pricing_shows_the_limits_from_the_plan_settings(): void
    {
        config([
            'bandaros.plans.free.max_products' => 250,
            'bandaros.plans.free.max_members' => 3,
            'bandaros.plans.pro.price' => 'RM 79',
        ]);

        $this->get('/pricing')
            ->assertOk()
            ->assertSee('Up to 250 products')
            ->assertSee('Up to 3 team members')
            ->assertSee('Unlimited products')
            ->assertSee('RM 79')
            ->assertSee('Import from Excel / CSV');
    }

    #[Test]
    public function the_home_page_describes_itself_to_search_engines_and_link_previews(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<meta name="description"', false)
            ->assertSee('<meta property="og:image" content="'.asset('images/dashboard.jpg').'">', false);
    }

    #[Test]
    public function every_page_shows_the_logo_and_the_svg_favicon(): void
    {
        foreach (['/', '/pricing', '/login'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('<link rel="icon" href="'.asset('favicon.svg').'" type="image/svg+xml">', false)
                ->assertSee('viewBox="0 0 240 315"', false);
        }
    }

    #[Test]
    public function only_the_home_page_loads_the_hero_animation(): void
    {
        $heroScript = Vite::asset('resources/js/hero.js');

        $this->get('/')->assertOk()->assertSee($heroScript, false)->assertSee('role="tablist"', false);
        $this->get('/pricing')->assertOk()->assertDontSee($heroScript, false);
    }
}
