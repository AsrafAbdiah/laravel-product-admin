<?php

namespace App\Support;

use App\Models\Team;
use InvalidArgumentException;

/**
 * What a team's plan allows, and how much of it they've used.
 * Plans are defined in config/bandaros.php.
 */
class PlanLimits
{
    /**
     * @return array<string, array{name: string, price: string, period: string, tagline: string, max_products: ?int, max_members: ?int, import: bool}>
     */
    public function plans(): array
    {
        return config('bandaros.plans');
    }

    /**
     * @return array{name: string, price: string, period: string, tagline: string, max_products: ?int, max_members: ?int, import: bool}
     */
    public function plan(Team $team): array
    {
        return $this->plans()[$team->plan] ?? throw new InvalidArgumentException("Unknown plan [{$team->plan}].");
    }

    public function productCount(Team $team): int
    {
        return $team->products()->count();
    }

    /**
     * Members plus people invited but not joined yet: an invitation holds a seat.
     */
    public function seatCount(Team $team): int
    {
        return $team->users()->count() + $team->invitations()->count();
    }

    public function canAddProducts(Team $team, int $count = 1): bool
    {
        $max = $this->plan($team)['max_products'];

        return $max === null || $this->productCount($team) + $count <= $max;
    }

    public function canAddMembers(Team $team): bool
    {
        $max = $this->plan($team)['max_members'];

        return $max === null || $this->seatCount($team) < $max;
    }

    public function allowsImport(Team $team): bool
    {
        return $this->plan($team)['import'];
    }

    /**
     * Usage figures for the plan card on the Team page.
     *
     * @return array<string, array{used: int, max: ?int}>
     */
    public function usage(Team $team): array
    {
        $plan = $this->plan($team);

        return [
            'products' => ['used' => $this->productCount($team), 'max' => $plan['max_products']],
            'members' => ['used' => $this->seatCount($team), 'max' => $plan['max_members']],
        ];
    }
}
