<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    /**
     * Determine if the user can view the location.
     */
    public function view(?User $user, Location $location): bool
    {
        if ($location->status === 'approved') {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->is_admin) {
            return true;
        }

        return $location->status === 'pending' && $user->id === $location->submitted_by;
    }

    /**
     * Determine if the user can update the location.
     */
    public function update(User $user, Location $location): bool
    {
        return $user->id === $location->submitted_by;
    }

    /**
     * Determine if the user can delete the location.
     */
    public function delete(User $user, Location $location): bool
    {
        return $user->id === $location->submitted_by;
    }
}
