<?php

namespace App\Jobs;

use App\Models\Team;
use App\Models\User;
use App\Notifications\ImportFinishedNotification;
use App\Services\ProductService;
use App\Support\CurrentTeam;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Imports a large uploaded file in the background, then tells the user
 * how it went.
 */
class ImportProductsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $teamId,
        public int $userId,
        public string $path,
        public string $originalName,
    ) {}

    public function handle(ProductService $products, CurrentTeam $currentTeam): void
    {
        $team = Team::findOrFail($this->teamId);
        $user = User::findOrFail($this->userId);

        // No request here: set the team (for scoping and new rows) and the
        // user (so stock history and the audit log say who imported).
        $currentTeam->set($team);
        Auth::setUser($user);

        try {
            $result = $products->importStoredFile($this->path, 'local', $this->originalName);
        } finally {
            Storage::disk('local')->delete($this->path);
        }

        $user->notify(new ImportFinishedNotification($team, $this->originalName, $result));
    }
}
