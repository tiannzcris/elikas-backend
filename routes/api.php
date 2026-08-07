<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\BarangayController;
use App\Http\Controllers\Api\EvacuationCenterController;
use App\Http\Controllers\Api\EvacuationEventController;
use App\Http\Controllers\Api\EvacueeController;
use App\Http\Controllers\Api\FamilyController;
use App\Http\Controllers\Api\GisController;
use App\Http\Controllers\Api\HazardProneAreaController;
use App\Http\Controllers\Api\PredictiveAnalyticsController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SystemLogController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes -- prefixed /api/v1/...
|--------------------------------------------------------------------------
|
| Versioned from day one (v1) so that if the mobile app is out in the field
| on an older contract when the API changes later, it doesn't break --
| a new v2 can be added alongside it instead of overwriting v1.
*/

Route::prefix('v1')->group(function () {

    // Public: staff login. Rate-limited to slow down credential-stuffing
    // attempts against a government system.
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1');

    // Protected: requires a valid Sanctum bearer token.
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Step 3: Evacuee & Family registration module. Role-scoped: all
        // three staff roles may register/view evacuees, but FamilyController
        // and EvacueeController further restrict barangay officials to their
        // own barangay's data internally (see AuthorizesBarangayAccess).
        Route::middleware('role:administrator,cswd_personnel,barangay_official')->group(function () {
            Route::post('/families/register', [FamilyController::class, 'store']);
            Route::get('/families', [FamilyController::class, 'index']);
            Route::get('/families/{family}', [FamilyController::class, 'show']);
            Route::post('/families/{family}/members', [EvacueeController::class, 'addMember']);

            Route::get('/evacuees', [EvacueeController::class, 'index']);
            Route::patch('/evacuees/{evacuee}', [EvacueeController::class, 'update']);
            Route::post('/evacuees/{evacuee}/check-out', [EvacueeController::class, 'checkOut']);

            // Minimal lookup endpoints so the registration form has data to
            // populate its dropdowns with.
            Route::get('/barangays', [BarangayController::class, 'index']);
            Route::get('/evacuation-events', [EvacuationEventController::class, 'index']);
            Route::get('/evacuation-events/{evacuationEvent}', [EvacuationEventController::class, 'show']);

            // Step 4: Evacuation Center + GIS module. Viewing is open to all
            // three staff roles (a barangay official needs to see occupancy
            // and hazard zones just as much as CSWD does) -- only
            // creating/editing centers and hazard zones is restricted
            // further below, since those are city-wide infrastructure, not
            // barangay-level data.
            //
            // IMPORTANT: '/evacuation-centers/nearest' must be registered
            // BEFORE '/evacuation-centers/{evacuationCenter}' below, or
            // Laravel will try to resolve "nearest" as a numeric center ID
            // via route-model binding and 404 instead of running the search.
            Route::get('/evacuation-centers', [EvacuationCenterController::class, 'index']);
            Route::get('/evacuation-centers/nearest', [EvacuationCenterController::class, 'nearest']);
            Route::get('/evacuation-centers/{evacuationCenter}', [EvacuationCenterController::class, 'show']);

            Route::get('/hazard-areas', [HazardProneAreaController::class, 'index']);
            Route::get('/hazard-areas/{hazardProneArea}', [HazardProneAreaController::class, 'show']);

            Route::get('/gis/map-data', [GisController::class, 'mapData']);

            // Step 5: Alerts -- viewing is open to all staff roles; only
            // sending a new alert is restricted further below (city-wide
            // broadcasts are a CSWD/admin responsibility, same pattern as
            // evacuation center management).
            Route::get('/alerts', [AlertController::class, 'index']);
            Route::get('/alerts/{alert}', [AlertController::class, 'show']);

            // Step 6: DROMIC report generation -- viewing/downloading is
            // open to all staff roles; only generating a NEW report is
            // restricted further below (report generation queries and
            // writes files, same access level as sending alerts).
            Route::get('/reports', [ReportController::class, 'index']);
            Route::get('/reports/dromic-region-v/preview', [ReportController::class, 'previewDromicRegionV']);
            Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');

            // Step 7: Predictive analytics -- viewing status/history is
            // open to all staff roles; generating a NEW forecast is
            // restricted further below (same access level as sending
            // alerts and generating reports -- a planning-level action).
            Route::get('/analytics/status', [PredictiveAnalyticsController::class, 'status']);
            Route::get('/predictions', [PredictiveAnalyticsController::class, 'index']);

            // Unlike DROMIC Region V (city-wide, admin/CSWD only), the EC
            // Information Board is scoped to a single center -- reasonably
            // a barangay-level concern, matching Chapter 1's promise of
            // "barangay-level reports" for barangay officials. Restricted
            // to their own barangay's centers at the controller level, not
            // here in routing.
            Route::post('/reports/ec-information-board', [ReportController::class, 'generateEcBoard']);
        });

        // Management-only: creating/editing evacuation centers and hazard
        // zones is restricted to administrator and CSWD personnel.
        Route::middleware('role:administrator,cswd_personnel')->group(function () {
            Route::post('/evacuation-events', [EvacuationEventController::class, 'store']);
            Route::patch('/evacuation-events/{evacuationEvent}', [EvacuationEventController::class, 'update']);

            Route::post('/evacuation-centers', [EvacuationCenterController::class, 'store']);
            Route::patch('/evacuation-centers/{evacuationCenter}', [EvacuationCenterController::class, 'update']);
            Route::put('/evacuation-centers/{evacuationCenter}/facilities', [EvacuationCenterController::class, 'updateFacilities']);

            Route::post('/hazard-areas', [HazardProneAreaController::class, 'store']);
            Route::delete('/hazard-areas/{hazardProneArea}', [HazardProneAreaController::class, 'destroy']);

            Route::post('/alerts', [AlertController::class, 'store']);

            Route::post('/reports/dromic-region-v', [ReportController::class, 'generateDromicRegionV']);

            Route::post('/predictions', [PredictiveAnalyticsController::class, 'store']);
        });

        // Administrator-only: account creation/management is deliberately
        // more restricted than the broader staff modules above -- even
        // CSWD personnel don't get to create or deactivate accounts.
        Route::middleware('role:administrator')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::patch('/users/{user}', [UserController::class, 'update']);

            Route::get('/system-logs', [SystemLogController::class, 'index']);
        });
    });

    // Public, unauthenticated: read-only endpoints the Flutter resident app
    // calls without a login, per the offline/read-only scope in Chapter 1.
    // Deliberately grouped under PublicController (rather than reusing the
    // staff-facing controllers directly) so it's obvious at a glance,
    // scanning this routes file, exactly what this system exposes without
    // authentication -- and so the fields returned can be hand-picked for
    // public appropriateness (e.g. no camp manager contact numbers).
    Route::get('/public/evacuation-centers', [PublicController::class, 'evacuationCenters']);
    Route::get('/public/evacuation-centers/nearest', [PublicController::class, 'nearestEvacuationCenters']);
    Route::get('/public/alerts', [PublicController::class, 'alerts']);
    // Reuses GisController::mapData() directly (not wrapped) -- its fields
    // were already checked and don't expose anything staff-only, so no
    // separate public-specific version is needed for this one.
    Route::get('/public/gis/map-data', [GisController::class, 'mapData']);
});
