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

    // See TileProxyController. OSM's own tile usage policy requires "a
    // clear, unique User-Agent string that names your app and optionally
    // includes a contact URL or email" -- their own documented example is
    // "MyTownMaps/1.4 (+https://example.org; contact: maps@example.org)",
    // which this default mirrors exactly. config('app.url') (not a
    // hardcoded domain) so this is automatically correct once production's
    // own APP_URL is set, the same way WelcomeUserMail's links already work.
    'tile_user_agent' => env(
        'TILE_USER_AGENT',
        'E-LIKAS/1.0 (+' . env('APP_URL', 'http://localhost') . '; contact: ' . env('ADMIN_SEED_EMAIL', 'admin@elikas.ligaocity.gov.ph') . ')'
    ),

    // Policy: "cache each tile for at least 7 days" when OSM's own
    // Cache-Control/Expires response headers aren't usable -- this is a
    // FLOOR the proxy enforces even when a header IS present and specifies
    // something shorter, never a ceiling (a longer header-specified value
    // always wins). See TileProxyController::resolveExpiry().
    'tile_cache_min_days' => env('TILE_CACHE_MIN_DAYS', 7),
];
