<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        // Public uploads are served by the public bucket/prefix when S3 is enabled.
        'public' => env('PUBLIC_STORAGE_DRIVER', 'local') === 's3'
            ? [
                'driver' => 's3',
                'key' => env('SUPABASE_STORAGE_ACCESS_KEY_ID'),
'secret' => env('SUPABASE_STORAGE_SECRET_ACCESS_KEY'),
                'region' => env('AWS_DEFAULT_REGION'),
                'bucket' => env('AWS_PUBLIC_BUCKET', env('AWS_BUCKET')),
                // Separate Supabase buckets store objects at their bucket root by default.
                // Keep the legacy prefixes only when using the older single-bucket setup.
                'root' => trim((string) env('AWS_PUBLIC_PREFIX', env('AWS_PUBLIC_BUCKET') ? '' : 'public'), '/'),
                'url' => env('AWS_PUBLIC_URL', env('AWS_URL')),
                'endpoint' => env('AWS_ENDPOINT'),
                'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
                'throw' => true,
                'report' => false,
            ]
            : [
                'driver' => 'local',
                'root' => storage_path('app/public'),
                'url' => rtrim(env('APP_URL', 'http://localhost'), '/') . '/storage',
                'visibility' => 'public',
                'throw' => false,
                'report' => false,
            ],

        // Private uploads are never given public object URLs; controllers authorize
        // access and stream them through Laravel routes.
        'private' => env('PRIVATE_STORAGE_DRIVER', 'local') === 's3'
            ? [
                'driver' => 's3',
                'key' => env('SUPABASE_STORAGE_ACCESS_KEY_ID'),
'secret' => env('SUPABASE_STORAGE_SECRET_ACCESS_KEY'),
                'region' => env('AWS_DEFAULT_REGION'),
                'bucket' => env('AWS_PRIVATE_BUCKET', env('AWS_BUCKET')),
                // Separate Supabase buckets store objects at their bucket root by default.
                // Keep the legacy prefixes only when using the older single-bucket setup.
                'root' => trim((string) env('AWS_PRIVATE_PREFIX', env('AWS_PRIVATE_BUCKET') ? '' : 'private'), '/'),
                'endpoint' => env('AWS_ENDPOINT'),
                'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
                'throw' => true,
                'report' => false,
            ]
            : [
                'driver' => 'local',
                'root' => storage_path('app/private'),
                'throw' => false,
                'report' => false,
            ],

        // Explicit S3 disk retained for code/integrations that need the raw bucket.
        's3' => [
            'driver' => 's3',
            'key' => env('SUPABASE_STORAGE_ACCESS_KEY_ID'),
'secret' => env('SUPABASE_STORAGE_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
