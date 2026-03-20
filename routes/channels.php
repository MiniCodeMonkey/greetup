<?php

use App\Models\Event;
use App\Models\EventChatMessage;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('event.{eventId}.chat', function ($user, int $eventId) {
    $event = Event::find($eventId);

    if (! $event) {
        return false;
    }

    return Gate::allows('send', [EventChatMessage::class, $event]);
});
