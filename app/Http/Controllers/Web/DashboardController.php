<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\DashboardService;
use App\Services\OnboardingChecklist;
use App\Support\CurrentTeam;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard, OnboardingChecklist $onboarding, CurrentTeam $currentTeam): View
    {
        $this->authorize('viewAny', Product::class);

        return view('dashboard', [
            'onboarding' => $onboarding->forDashboard($currentTeam->get(), $request->user()),
            'totals' => $dashboard->totals(),
            'categories' => $dashboard->productsPerCategory(),
            'lowStock' => $dashboard->lowStockProducts(),
            'movements' => $dashboard->recentStockMovements(),
        ]);
    }
}
