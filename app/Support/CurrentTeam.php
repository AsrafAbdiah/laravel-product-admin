<?php

namespace App\Support;

use App\Models\Team;

/**
 * Holds the team the current request is working for.
 * Set once by the SetCurrentTeam middleware, read everywhere else.
 */
class CurrentTeam
{
    private ?Team $team = null;

    public function set(?Team $team): void
    {
        $this->team = $team;
    }

    public function get(): ?Team
    {
        return $this->team;
    }

    public function id(): ?int
    {
        return $this->team?->id;
    }
}
