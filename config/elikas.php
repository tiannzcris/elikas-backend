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

    // Passed through url() in WelcomeUserMail, so a relative path here
    // (the installer lives in public/downloads/, uploaded directly to the
    // server rather than committed -- see .gitignore) resolves against
    // APP_URL into a full absolute link in the email.
    'desktop_app_download_url' => env('DESKTOP_APP_DOWNLOAD_URL', '/downloads/E-LIKAS-Setup.exe'),

    // Same pattern as the desktop installer above -- the resident-facing
    // Flutter app's .apk is built and uploaded separately, not committed.
    'mobile_app_download_url' => env('MOBILE_APP_DOWNLOAD_URL', '/downloads/E-LIKAS-Mobile.apk'),
];
