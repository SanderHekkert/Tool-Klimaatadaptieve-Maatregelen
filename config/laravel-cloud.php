<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel Cloud object storage (injected on Cloud)
    |--------------------------------------------------------------------------
    |
    | JSON array met bucket-disks; wordt in AppServiceProvider omgezet naar
    | filesystems.disks.* configuratie (bijv. disk-naam "private").
    |
    */

    'disk_config_json' => env('LARAVEL_CLOUD_DISK_CONFIG'),

    'disk_names' => [],

];
