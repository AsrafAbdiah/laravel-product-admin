<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Support\CurrentTeam;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The audit log: who did what, and when, in the current team.
 */
class ActivityController extends Controller
{
    public const AREAS = [
        'products' => 'Products',
        'categories' => 'Categories',
        'team' => 'Team',
    ];

    public function index(Request $request, CurrentTeam $currentTeam): View
    {
        $this->authorize('viewActivity', $currentTeam->get());

        $area = $request->query('area');
        $area = array_key_exists((string) $area, self::AREAS) ? $area : null;

        // Activity is team-scoped (BelongsToTeam), so this is only this team's log.
        $activities = Activity::query()
            ->with('causer:id,name')
            ->when($area, fn ($query) => $query->where('log_name', $area))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('activity.index', [
            'activities' => $activities,
            'area' => $area,
            'areas' => self::AREAS,
        ]);
    }
}
