<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| These just render Blade shells -- there's no server-side session auth
| here. Every page's JS calls Api.requireAuth() on load (see layouts/app.blade.php),
| which checks for a Sanctum token in localStorage and bounces to /login if
| there isn't one. The actual data on each page comes from /api/v1/... calls
| made client-side, using that same token.
*/

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/login', function () {
    return view('auth.login');
});

// A real landing page for the installer, not a direct link straight to the
// .exe -- Gmail's receiving-side filters silently drop emails containing a
// direct link to an .exe file (no bounce, no error, nothing in our logs;
// see WelcomeUserMail). This page is what the welcome email links to
// instead; the actual installer link lives here, one click away.
Route::get('/download/desktop-app', function () {
    return view('download-desktop-app');
});

// Same reasoning as the desktop installer above -- a real landing page for
// the resident-facing Flutter app's .apk, not a raw file link.
Route::get('/download/mobile-app', function () {
    return view('download-mobile-app');
});

// Needed for the Microsoft SmartScreen dispute form submitted for the
// desktop installer -- publicly reachable, no auth, static content only.
Route::get('/privacy', function () {
    return view('privacy');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

Route::get('/families', function () {
    return view('families.index');
});

Route::get('/families/create', function () {
    return view('families.create');
});

Route::get('/families/{id}', function (string $id) {
    return view('families.show');
})->where('id', '[0-9]+');

Route::get('/evacuation-centers', function () {
    return view('evacuation-centers.index');
});

Route::get('/evacuation-events', function () {
    return view('evacuation-events.index');
});

Route::get('/evacuation-events/create', function () {
    return view('evacuation-events.create');
});

Route::get('/evacuation-events/{id}/edit', function (string $id) {
    return view('evacuation-events.create');
})->where('id', '[0-9]+');

Route::get('/evacuation-centers/create', function () {
    return view('evacuation-centers.create');
});

Route::get('/evacuation-centers/{id}/edit', function (string $id) {
    return view('evacuation-centers.create');
})->where('id', '[0-9]+');

Route::get('/evacuation-centers/{id}', function (string $id) {
    return view('evacuation-centers.show');
})->where('id', '[0-9]+');

Route::get('/gis-map', function () {
    return view('gis.map');
});

Route::get('/alerts', function () {
    return view('alerts.index');
});

Route::get('/alerts/create', function () {
    return view('alerts.create');
});

Route::get('/reports', function () {
    return view('reports.index');
});

Route::get('/users', function () {
    return view('users.index');
});

Route::get('/users/create', function () {
    return view('users.create');
});

Route::get('/users/{id}/edit', function (string $id) {
    return view('users.create');
})->where('id', '[0-9]+');

Route::get('/predictive-analytics', function () {
    return view('predictive-analytics.index');
});
