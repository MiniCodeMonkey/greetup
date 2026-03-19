<?php

namespace App\Observers;

use App\Jobs\GeocodeLocation;
use App\Models\Event;

class EventObserver
{
    public function created(Event $event): void
    {
        if ($event->venue_address !== null && $event->venue_address !== '') {
            GeocodeLocation::dispatch($event, 'venue_address', 'venue_latitude', 'venue_longitude');
        }
    }

    public function updated(Event $event): void
    {
        if ($event->wasChanged('venue_address') && $event->venue_address !== null && $event->venue_address !== '') {
            GeocodeLocation::dispatch($event, 'venue_address', 'venue_latitude', 'venue_longitude');
        }
    }
}
