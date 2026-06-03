<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BasisgidsUploadCommand extends Command
{
    protected $signature = 'basisgids:upload
                            {file? : Pad naar de PDF (standaard: public/documents/DUU- Basisgids klimaatadaptatie.pdf)}
                            {--disk= : Storage-disk (standaard: BASISGIDS_DISK of s3)}';

    protected $description = 'Upload de Basisgids-PDF naar cloud storage (bijv. Laravel Cloud Object Storage)';

    public function handle(): int
    {
        $diskName = $this->option('disk') ?: config('basisgids.disk') ?: 's3';

        if (! config("filesystems.disks.{$diskName}")) {
            $this->error("Onbekende disk \"{$diskName}\". Stel BASISGIDS_DISK in of gebruik --disk=s3.");

            return self::FAILURE;
        }

        if (! config("filesystems.disks.{$diskName}.key") && $diskName === 's3') {
            $this->error('Geen S3-credentials gevonden (AWS_ACCESS_KEY_ID). Koppel Object Storage in Laravel Cloud of vul .env in.');

            return self::FAILURE;
        }

        $source = $this->argument('file')
            ?? public_path('documents/DUU- Basisgids klimaatadaptatie.pdf');

        if (! is_readable($source)) {
            $this->error("Bestand niet gevonden of niet leesbaar: {$source}");

            return self::FAILURE;
        }

        $handle = fopen($source, 'rb');
        if ($handle === false || fread($handle, 4) !== '%PDF') {
            if (is_resource($handle)) {
                fclose($handle);
            }
            $this->error('Geen geldig PDF-bestand (controleer of Git LFS is uitgepakt: git lfs pull).');

            return self::FAILURE;
        }
        fclose($handle);

        $target = config('basisgids.storage_path');
        $this->info("Uploaden naar disk \"{$diskName}\" als {$target}…");

        $stream = fopen($source, 'rb');
        Storage::disk($diskName)->writeStream($target, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        $this->info('Klaar. Zet in Laravel Cloud (Environment):');
        $this->line("  BASISGIDS_DISK={$diskName}");
        $this->line("  BASISGIDS_STORAGE_PATH={$target}");
        $this->info('Deploy opnieuw en test /basisgids-klimaatadaptatie.pdf');

        return self::SUCCESS;
    }
}
