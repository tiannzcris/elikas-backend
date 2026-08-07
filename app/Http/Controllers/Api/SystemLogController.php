<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SystemLogResource;
use App\Models\SystemLog;
use Illuminate\Http\Request;

class SystemLogController extends Controller
{
    /**
     * Read-only audit trail for the User management screen's "recent
     * activity" feed -- administrator-only, same as the rest of the
     * account-management endpoints in this route group.
     */
    public function index(Request $request)
    {
        $logs = SystemLog::query()
            ->with('user')
            ->latest('created_at')
            ->paginate($request->integer('per_page', 20));

        return $this->success(SystemLogResource::collection($logs)->response()->getData(true));
    }
}
