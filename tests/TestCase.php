<?php

namespace Tests;

use App\Models\Team;
use App\Support\CurrentTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

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
}
