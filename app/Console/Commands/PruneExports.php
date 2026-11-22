<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('bandaros:prune-exports')]
#[Description('Delete finished export files older than the configured number of days')]
class PruneExports extends Command
{
    public function handle(): int
    {
        $disk = Storage::disk('local');
        $cutoff = now()->subDays(config('bandaros.keep_exports_for_days'))->getTimestamp();
        $deleted = 0;

        foreach ($disk->allFiles('exports') as $file) {
            if ($disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
                $deleted++;
            }
        }

        $this->info("Deleted {$deleted} old export file(s).");

        return self::SUCCESS;
    }
}
