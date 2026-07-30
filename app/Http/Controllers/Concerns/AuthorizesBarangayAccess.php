<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;

/**
 * Barangay officials may only see/register evacuees for their own barangay;
 * administrators and CSWD personnel operate city-wide. Kept as a small
 * shared trait rather than a Policy class, since Laravel's policy
 * auto-discovery behavior isn't something this project can verify without
 * running it -- a plain method here is simple enough to read and trust.
 */
trait AuthorizesBarangayAccess
{
    protected function userMayAccessBarangay(User $user, int $barangayId): bool
    {
        if ($user->isBarangayOfficial()) {
            return $user->barangay_id === $barangayId;
        }

        return $user->isAdministrator() || $user->isCswdPersonnel();
    }
}
