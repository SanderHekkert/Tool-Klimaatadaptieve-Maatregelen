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
    | Leeg laten = automatisch zoeken op FILESYSTEM_DISK, alle s3-disks en "s3".
    | Bij Laravel Cloud: kijk in Environment welke disk-naam je bij de bucket koos.
    */
    'disk' => env('BASISGIDS_DISK'),

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
