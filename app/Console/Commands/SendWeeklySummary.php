<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Notifications\WeeklySummaryNotification;
use App\Services\DashboardService;
use App\Support\CurrentTeam;
use App\Support\Roles;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

#[Signature('bandaros:weekly-summary')]
#[Description('Email each shop\'s owners and admins a summary of their stock')]
class SendWeeklySummary extends Command
{
    public function handle(DashboardService $dashboard, CurrentTeam $currentTeam): int
    {
        $sent = 0;

        Team::query()->chunkById(100, function ($teams) use ($dashboard, $currentTeam, &$sent) {
            foreach ($teams as $team) {
                // Work "inside" each team so the dashboard numbers are its own.
                $currentTeam->set($team);

                $totals = $dashboard->totals();
                if ($totals['products'] === 0) {
                    continue;
                }

                $lowStock = $dashboard->lowStockProducts(10)
                    ->map(fn ($product) => ['name' => $product->name, 'stock' => $product->stock])
                    ->all();

                Notification::send(
                    $team->membersWithRole([Roles::OWNER, Roles::ADMIN]),
                    new WeeklySummaryNotification($team, $totals, $lowStock),
                );
                $sent++;
            }
        });

        $currentTeam->set(null);
        $this->info("Weekly summary sent for {$sent} shop(s).");

        return self::SUCCESS;
    }
}
