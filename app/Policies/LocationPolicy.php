<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    /**
     * Determine if the user can delete the location.
     */
    public function delete(User $user, Location $location): bool
    {
        return $user->id === $location->submitted_by;
    }
}
