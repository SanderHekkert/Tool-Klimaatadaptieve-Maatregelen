<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Basisgids PDF (Van Wijnen)
    |--------------------------------------------------------------------------
    |
    | Lokaal: plaats het bestand in public/documents/ of storage/app/documents/.
    |
    | Laravel Cloud: Git LFS werkt niet. Gebruik één van deze opties:
    | 1) Object Storage-bucket koppelen, PDF uploaden, BASISGIDS_DISK=s3 zetten.
    | 2) Publieke URL naar de PDF: BASISGIDS_PDF_URL=https://...
    |
    */

    'external_url' => env('BASISGIDS_PDF_URL'),

    /*
    | Laravel Cloud: FILESYSTEM_DISK is vaak "private" (niet "s3") — zie LARAVEL_CLOUD_DISK_CONFIG.
    | Leeg laten = zelfde disk als FILESYSTEM_DISK.
    */
    'disk' => env('BASISGIDS_DISK', env('FILESYSTEM_DISK')),

    'storage_path' => env('BASISGIDS_STORAGE_PATH', 'documents/basisgids-klimaatadaptatie.pdf'),

    'storage_paths' => [
        'documents/basisgids-klimaatadaptatie.pdf',
        'basisgids-klimaatadaptatie.pdf',
        'documents/DUU- Basisgids klimaatadaptatie.pdf',
        'DUU- Basisgids klimaatadaptatie.pdf',
    ],

    'local_paths' => [
        storage_path('app/documents/basisgids-klimaatadaptatie.pdf'),
        public_path('documents/DUU- Basisgids klimaatadaptatie.pdf'),
    ],

];
