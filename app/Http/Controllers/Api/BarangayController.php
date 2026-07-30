<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barangay;

class BarangayController extends Controller
{
    /**
     * Lightweight lookup list for form dropdowns. Full barangay management
     * (creating/editing barangays) isn't part of this system's scope --
     * Ligao City's barangays are seeded once as reference data, not
     * maintained through the app.
     */
    public function index()
    {
        return $this->success(
            Barangay::orderBy('name')->get(['id', 'name'])
        );
    }
}
