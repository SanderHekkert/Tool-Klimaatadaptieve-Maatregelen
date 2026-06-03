<?php

namespace App\Console\Commands;

use App\Support\BasisgidsStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BasisgidsStatusCommand extends Command
{
    protected $signature = 'basisgids:status';

    protected $description = 'Controleer of de Basisgids-PDF bereikbaar is via object storage (Laravel Cloud / R2)';

    public function handle(): int
    {
        $this->info('Basisgids — diagnose');
        $this->line('AWS_BUCKET: '.(env('AWS_BUCKET') ?: '—'));
        $this->line('AWS_ENDPOINT: '.(env('AWS_ENDPOINT') ?: '—'));
        $this->line('FILESYSTEM_DISK: '.(config('filesystems.default') ?: '—'));
        $this->line('BASISGIDS_DISK: '.(config('basisgids.disk') ?: '(auto)'));
        $this->newLine();

        $paths = BasisgidsStorage::storagePaths();

        $disks = BasisgidsStorage::diskCandidates();

        foreach ($disks as $diskName) {
            if (! config("filesystems.disks.{$diskName}")) {
                $this->warn("Disk \"{$diskName}\": niet geconfigureerd");

                continue;
            }

            $this->info("Disk \"{$diskName}\":");

            foreach ($paths as $path) {
                try {
                    $exists = Storage::disk($diskName)->exists($path);
                    $this->line('  '.($exists ? '✓' : '✗').' '.$path);
                } catch (\Throwable $e) {
                    $this->line('  ! '.$path.' — '.$e->getMessage());
                }
            }
        }

        return self::SUCCESS;
    }
}
