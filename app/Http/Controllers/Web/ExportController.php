<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\ExportProductsJob;
use App\Models\Product;
use App\Support\CurrentTeam;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads exports that were built in the background.
 */
class ExportController extends Controller
{
    public function download(string $fileId, CurrentTeam $currentTeam): StreamedResponse
    {
        $this->authorize('export', Product::class);

        // Files are stored per team, so only members of the right team (in
        // that team right now) can reach them; other ids are just "not found".
        $path = ExportProductsJob::path($currentTeam->id(), $fileId);
        abort_unless(Storage::disk('local')->exists($path), 404, 'This export has expired or does not exist.');

        return Storage::disk('local')->download($path, 'products-export.xlsx');
    }
}
