<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboard): View
    {
        return view('dashboard', [
            'totals' => $dashboard->totals(),
            'categories' => $dashboard->productsPerCategory(),
            'lowStock' => $dashboard->lowStockProducts(),
            'movements' => $dashboard->recentStockMovements(),
        ]);
    }
}
