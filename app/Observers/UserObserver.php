<?php

namespace App\Observers;

use App\Jobs\GeocodeLocation;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        if ($user->location !== null && $user->location !== '') {
            GeocodeLocation::dispatch($user, 'location', 'latitude', 'longitude');
        }
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged('location') && $user->location !== null && $user->location !== '') {
            GeocodeLocation::dispatch($user, 'location', 'latitude', 'longitude');
        }
    }
}
