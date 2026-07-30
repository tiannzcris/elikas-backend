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
