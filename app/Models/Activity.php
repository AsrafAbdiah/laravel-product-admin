<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * An audit log entry ("who did what, when"), scoped to the current team
 * like products and categories. New entries get the current team's id
 * unless the code logging them sets one explicitly.
 */
class Activity extends SpatieActivity
{
    use BelongsToTeam;

    /**
     * The changed fields as [field => [old, new]], for display.
     *
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public function fieldChanges(): array
    {
        $new = $this->properties->get('attributes', []);
        $old = $this->properties->get('old', []);

        return collect($new)
            ->mapWithKeys(fn ($value, $field) => [$field => [$old[$field] ?? null, $value]])
            ->all();
    }
}
