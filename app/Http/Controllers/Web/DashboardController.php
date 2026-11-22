<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboard): View
    {
        $this->authorize('viewAny', Product::class);

        return view('dashboard', [
            'totals' => $dashboard->totals(),
            'categories' => $dashboard->productsPerCategory(),
            'lowStock' => $dashboard->lowStockProducts(),
            'movements' => $dashboard->recentStockMovements(),
        ]);
    }
}
