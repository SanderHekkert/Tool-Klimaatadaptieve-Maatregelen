<?php

namespace App\Console\Commands;

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

        $paths = array_values(array_unique(array_filter([
            config('basisgids.storage_path'),
            ...config('basisgids.storage_paths', []),
        ], static fn (mixed $p): bool => is_string($p) && $p !== '')));

        $disks = $this->diskCandidates();

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

    /**
     * @return list<string>
     */
    private function diskCandidates(): array
    {
        $candidates = [];

        $explicit = config('basisgids.disk');
        if (is_string($explicit) && $explicit !== '') {
            $candidates[] = $explicit;
        }

        $default = config('filesystems.default');
        if (is_string($default) && $default !== '') {
            $candidates[] = $default;
        }

        $cloudConfig = env('LARAVEL_CLOUD_DISK_CONFIG');
        if (is_string($cloudConfig) && $cloudConfig !== '') {
            $decoded = json_decode($cloudConfig, true);
            if (is_array($decoded)) {
                foreach ($decoded as $entry) {
                    if (is_array($entry) && is_string($entry['disk'] ?? null) && $entry['disk'] !== '') {
                        $candidates[] = $entry['disk'];
                    }
                }
            }
        }

        $candidates[] = 's3';

        foreach (config('filesystems.disks', []) as $name => $diskConfig) {
            if (is_array($diskConfig) && ($diskConfig['driver'] ?? null) === 's3') {
                $candidates[] = $name;
            }
        }

        return array_values(array_unique($candidates));
    }
}
