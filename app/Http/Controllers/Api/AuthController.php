<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Issue a Sanctum personal access token for a staff account
     * (administrator, cswd_personnel, or barangay_official).
     *
     * Residents never hit this endpoint -- the mobile app's public
     * information endpoints (evacuation centers, alerts, hazard maps)
     * are read without authentication, per the scope in Chapter 1.
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        $user = User::with(['role', 'barangay'])
            ->where('email', $credentials['email'])
            ->first();

        // Checked in this order deliberately: verify the password before
        // revealing anything about account status, so a wrong-password
        // attempt on a suspended account can't be used to fingerprint
        // which accounts exist and which are disabled.
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->error('The provided credentials are incorrect.', 401);
        }

        if ($user->status !== 'active') {
            return $this->error("This account is currently {$user->status}. Contact the system administrator.", 403);
        }

        $token = $user->createToken("{$user->role->name}-token")->plainTextToken;

        SystemLog::create([
            'user_id' => $user->id,
            'action' => 'user.login',
            'description' => "{$user->name} ({$user->role->name}) logged in.",
            'ip_address' => $request->ip(),
        ]);

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'Logged in successfully.');
    }

    /**
     * Revoke only the token used for THIS request, not all of the user's
     * tokens -- so logging out on the web dashboard doesn't also sign the
     * same user out of the Flutter app on another device.
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        SystemLog::create([
            'user_id' => $user->id,
            'action' => 'user.logout',
            'description' => "{$user->name} logged out.",
            'ip_address' => $request->ip(),
        ]);

        return $this->success(null, 'Logged out successfully.');
    }

    public function me(Request $request)
    {
        return $this->success(
            new UserResource($request->user()->load(['role', 'barangay']))
        );
    }
}
