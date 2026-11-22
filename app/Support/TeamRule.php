<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/**
 * Validation rules limited to the current team.
 *
 * A plain "exists:categories,id" checks the whole table, so a user could
 * attach a product to another shop's category just by sending its id.
 * These versions only look at the current team's rows. With no current
 * team they match nothing, so they fail closed.
 */
final class TeamRule
{
    public static function exists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->where('team_id', app(CurrentTeam::class)->id());
    }

    public static function unique(string $table, string $column): Unique
    {
        return Rule::unique($table, $column)->where('team_id', app(CurrentTeam::class)->id());
    }
}
