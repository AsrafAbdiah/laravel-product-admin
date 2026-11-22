<?php

namespace App\Jobs;

use App\Exports\ProductsExport;
use App\Models\Team;
use App\Models\User;
use App\Notifications\ExportReadyNotification;
use App\Services\ProductService;
use App\Support\CurrentTeam;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Builds a large export in the background, saves it privately and tells
 * the user it's ready to download.
 */
class ExportProductsJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $filters  the same filters as the product list
     */
    public function __construct(
        public int $teamId,
        public int $userId,
        public array $filters,
        public string $fileId,
    ) {}

    public static function path(int $teamId, string $fileId): string
    {
        return "exports/{$teamId}/{$fileId}.xlsx";
    }

    public function handle(ProductService $products, CurrentTeam $currentTeam): void
    {
        $team = Team::findOrFail($this->teamId);

        // Queued jobs have no request, so set the team explicitly: the team
        // scope then limits the export to this shop's products.
        $currentTeam->set($team);

        $query = $products->query($this->filters);
        $rows = $query->count();

        Excel::store(new ProductsExport($query), self::path($team->id, $this->fileId), 'local');

        User::find($this->userId)?->notify(new ExportReadyNotification($team, $this->fileId, $rows));
    }
}
