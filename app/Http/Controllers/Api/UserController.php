<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Mail\WelcomeUserMail;
use App\Models\Role;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Real account management, replacing what previously only existed via
 * `php artisan tinker` -- not realistic for actual CSWDO staff to depend
 * on going forward. Every endpoint here is administrator-only (see
 * routes/api.php): creating and managing accounts is deliberately more
 * restricted than the day-to-day staff-facing modules elsewhere in this
 * system.
 */
class UserController extends Controller
{
    public function index()
    {
        $users = User::query()->with(['role', 'barangay'])->orderBy('name')->get();

        return $this->success(UserResource::collection($users));
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();
        $role = Role::where('name', $validated['role'])->firstOrFail();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'], // hashed automatically via the model's 'hashed' cast
            'contact_number' => $validated['contact_number'] ?? null,
            'role_id' => $role->id,
            'barangay_id' => $validated['barangay_id'] ?? null,
            'status' => 'active',
        ]);

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.created',
            'description' => "{$request->user()->name} created a new {$role->display_name} account for {$user->name}.",
            'ip_address' => $request->ip(),
        ]);

        // Mail failure should never block account creation itself -- same
        // "degrade gracefully" principle as Semaphore SMS elsewhere in this
        // system. The account is real and usable either way; only the
        // notification about it might not have gone out.
        $emailSent = true;
        try {
            Mail::to($user->email)->send(
                new WelcomeUserMail($user->name, $user->email, $validated['password'], $role->display_name)
            );
        } catch (\Throwable $e) {
            $emailSent = false;
            Log::warning("Welcome email failed to send for new user {$user->email}: {$e->getMessage()}");
        }

        return $this->success(
            new UserResource($user->load(['role', 'barangay'])),
            $emailSent
                ? 'Account created successfully. A welcome email with login details was sent.'
                : 'Account created successfully, but the welcome email could not be sent -- share the login details with them directly.',
            201
        );
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();
        $wasActive = $user->status === 'active';

        if (isset($validated['role'])) {
            $validated['role_id'] = Role::where('name', $validated['role'])->firstOrFail()->id;
            unset($validated['role']);
        }

        // Only touch the password if one was actually provided -- an
        // empty/missing value here should leave the existing password
        // completely alone, not overwrite it with something blank.
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        if ($wasActive && $user->status !== 'active') {
            SystemLog::create([
                'user_id' => $request->user()->id,
                'action' => 'user.deactivated',
                'description' => "{$request->user()->name} set {$user->name}'s account to {$user->status}.",
                'ip_address' => $request->ip(),
            ]);
        }

        return $this->success(
            new UserResource($user->fresh()->load(['role', 'barangay'])),
            'Account updated successfully.'
        );
    }
}
