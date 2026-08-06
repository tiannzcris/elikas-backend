<?php

// Read through config(), not env() directly, in the seeder -- this is the
// official Laravel-recommended pattern specifically to avoid the caching
// gotcha just hit during deployment: raw env() calls outside config files
// can return stale/empty values once `php artisan config:cache` has run,
// since config:cache snapshots config() values but has no way to know
// about env() calls buried inside application code like a seeder.
return [
    'admin_seed' => [
        'email' => env('ADMIN_SEED_EMAIL'),
        'password' => env('ADMIN_SEED_PASSWORD'),
    ],
];
