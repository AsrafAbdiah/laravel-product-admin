<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\PlanLimits;
use Illuminate\View\View;

/**
 * The public website: home, pricing and the legal pages. Open to everyone,
 * signed in or not.
 */
class MarketingController extends Controller
{
    public function __construct(private PlanLimits $limits) {}

    public function home(): View
    {
        return view('marketing.home', ['plans' => $this->plans()]);
    }

    public function pricing(): View
    {
        return view('marketing.pricing', [
            'plans' => $this->plans(),
            'comparison' => $this->comparison(),
        ]);
    }

    public function terms(): View
    {
        return view('marketing.terms');
    }

    public function privacy(): View
    {
        return view('marketing.privacy');
    }

    /**
     * The plans from config/bandaros.php, each with a list of what it includes,
     * so the pricing cards always match the limits the app enforces.
     *
     * @return array<string, array<string, mixed>>
     */
    private function plans(): array
    {
        return collect($this->limits->plans())->map(fn (array $plan) => $plan + [
            'features' => [
                $plan['max_products'] === null ? 'Unlimited products' : 'Up to '.number_format($plan['max_products']).' products',
                $plan['max_members'] === null ? 'Unlimited team members' : 'Up to '.$plan['max_members'].' team members',
                $plan['import'] ? 'Import from Excel / CSV' : 'Export to Excel / CSV',
                'Roles, audit log and two-factor login',
                'Low-stock alerts and REST API',
            ],
        ])->all();
    }

    /**
     * Rows for the plan comparison table: label => [plan key => value].
     * A value is text, or true / false for a tick or a dash.
     *
     * @return array<string, array<string, string|bool>>
     */
    private function comparison(): array
    {
        $plans = $this->limits->plans();
        $row = fn (callable $value) => array_map($value, $plans);
        $limit = fn (?int $max) => $max === null ? 'Unlimited' : number_format($max);

        return [
            'Products' => $row(fn ($plan) => $limit($plan['max_products'])),
            'Team members' => $row(fn ($plan) => $limit($plan['max_members'])),
            'Import from Excel / CSV' => $row(fn ($plan) => $plan['import']),
            'Export to Excel / CSV' => $row(fn () => true),
            'Roles: Owner, Admin, Staff, Viewer' => $row(fn () => true),
            'Audit log of every change' => $row(fn () => true),
            'Two-factor login' => $row(fn () => true),
            'Low-stock alerts and weekly summary' => $row(fn () => true),
            'REST API with read-only or read-write tokens' => $row(fn () => true),
        ];
    }
}
