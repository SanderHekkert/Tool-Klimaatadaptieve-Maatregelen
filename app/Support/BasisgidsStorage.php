<?php

namespace App\Support;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class BasisgidsStorage
{
    /**
     * Registreert disks uit LARAVEL_CLOUD_DISK_CONFIG (Laravel Cloud).
     */
    public static function registerCloudDisks(): void
    {
        $raw = config('laravel-cloud.disk_config_json');
        if (! is_string($raw) || $raw === '') {
            return;
        }

        $entries = json_decode($raw, true);
        if (! is_array($entries)) {
            return;
        }

        $names = [];

        foreach ($entries as $entry) {
            if (! is_array($entry) || ! is_string($entry['disk'] ?? null) || $entry['disk'] === '') {
                continue;
            }

            $name = $entry['disk'];
            $names[] = $name;

            Config::set("filesystems.disks.{$name}", [
                'driver' => 's3',
                'key' => $entry['access_key_id'] ?? '',
                'secret' => $entry['access_key_secret'] ?? '',
                'region' => $entry['default_region'] ?? 'auto',
                'bucket' => $entry['bucket'] ?? '',
                'url' => $entry['url'] ?? null,
                'endpoint' => $entry['endpoint'] ?? null,
                'use_path_style_endpoint' => (bool) ($entry['use_path_style_endpoint'] ?? false),
                'throw' => false,
                'report' => false,
            ]);
        }

        Config::set('laravel-cloud.disk_names', $names);
    }

    /**
     * @return list<string>
     */
    public static function diskCandidates(): array
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

        foreach (config('laravel-cloud.disk_names', []) as $name) {
            if (is_string($name) && $name !== '') {
                $candidates[] = $name;
            }
        }

        $candidates[] = 's3';

        foreach (config('filesystems.disks', []) as $name => $diskConfig) {
            if (! is_string($name) || ! is_array($diskConfig)) {
                continue;
            }
            if (($diskConfig['driver'] ?? null) === 's3') {
                $candidates[] = $name;
            }
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @return list<string>
     */
    public static function storagePaths(): array
    {
        return array_values(array_unique(array_filter([
            config('basisgids.storage_path'),
            ...config('basisgids.storage_paths', []),
        ], static fn (mixed $path): bool => is_string($path) && $path !== '')));
    }

    /**
     * @param  array<string, string>  $headers
     */
    public static function responseFromDisk(string $diskName, string $path, array $headers): ?Response
    {
        if (! config("filesystems.disks.{$diskName}")) {
            return null;
        }

        try {
            $disk = Storage::disk($diskName);
            if (! $disk->exists($path)) {
                return null;
            }

            /** @var FilesystemAdapter $disk */
            return $disk->response($path, 'Basisgids-Klimaatadaptatie-Van-Wijnen.pdf', $headers);
        } catch (\Throwable) {
            return null;
        }
    }
}
