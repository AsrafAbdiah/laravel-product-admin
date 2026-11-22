<?php

namespace Tests;

use App\Models\Team;
use App\Support\CurrentTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class TestCase extends BaseTestCase
{
    /**
     * The team that factories and users belong to by default in tests.
     */
    protected ?Team $defaultTeam = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Database tests run "inside" a default team, like a logged-in request,
        // so factories create data in the same team as the users acting on it.
        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class), true)) {
            $this->defaultTeam = Team::factory()->create(['name' => 'Test Shop']);
            app(CurrentTeam::class)->set($this->defaultTeam);
        }
    }

    /**
     * Like on a real server, every test request starts with no current team,
     * so the middleware has to set it. Afterwards the test's own team is put
     * back for any data the test creates next.
     *
     * @return TestResponse<Response>
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $currentTeam = app(CurrentTeam::class);
        $testTeam = $currentTeam->get();
        $currentTeam->set(null);

        try {
            return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
        } finally {
            $currentTeam->set($testTeam);
        }
    }
}
