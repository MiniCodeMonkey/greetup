<?php

namespace App\Observers;

use App\Jobs\GeocodeLocation;
use App\Models\Group;

class GroupObserver
{
    public function created(Group $group): void
    {
        if ($group->location !== null && $group->location !== '') {
            GeocodeLocation::dispatch($group, 'location', 'latitude', 'longitude');
        }
    }

    public function updated(Group $group): void
    {
        if ($group->wasChanged('location') && $group->location !== null && $group->location !== '') {
            GeocodeLocation::dispatch($group, 'location', 'latitude', 'longitude');
        }
    }
}
