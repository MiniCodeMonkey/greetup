<?php

namespace App\Livewire;

use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Group;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class DashboardPage extends Component
{
    public function render(): View
    {
        $user = Auth::user();
        $siteName = Setting::get('site_name', config('app.name', 'Greetup'));
        $displayTimezone = $user->timezone ?: Setting::get('default_timezone', 'UTC');

        $upcomingEvents = $this->getUpcomingEvents($user);
        $userGroups = $this->getUserGroups($user);
        $suggestedEvents = $this->getSuggestedEvents($user);
        $notifications = $user->unreadNotifications()->limit(10)->get();

        return view('livewire.dashboard-page', [
            'upcomingEvents' => $upcomingEvents,
            'userGroups' => $userGroups,
            'suggestedEvents' => $suggestedEvents,
            'notifications' => $notifications,
            'displayTimezone' => $displayTimezone,
        ])->layoutData([
            'title' => "Dashboard — {$siteName}",
            'description' => 'Your personalized dashboard with upcoming events, groups, and recommendations.',
        ]);
    }

    /**
     * @return Collection<int, Event>
     */
    private function getUpcomingEvents(mixed $user): Collection
    {
        $rsvpEventIds = $user->rsvps()
            ->where('status', RsvpStatus::Going)
            ->pluck('event_id');

        if ($rsvpEventIds->isEmpty()) {
            return collect();
        }

        return Event::query()
            ->whereIn('id', $rsvpEventIds)
            ->upcoming()
            ->with(['group'])
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * @return Collection<int, Group>
     */
    private function getUserGroups(mixed $user): Collection
    {
        return $user->groups()
            ->with(['events' => function ($query): void {
                $query->upcoming()->orderBy('starts_at')->limit(1);
            }])
            ->get();
    }

    /**
     * @return Collection<int, Event>
     */
    private function getSuggestedEvents(mixed $user): Collection
    {
        $rsvpEventIds = $user->rsvps()->pluck('event_id')->toArray();
        $userGroupIds = $user->groups()->pluck('groups.id')->toArray();

        // Events in user's groups they haven't RSVP'd to
        $groupEvents = Event::query()
            ->upcoming()
            ->whereIn('group_id', $userGroupIds)
            ->whereNotIn('id', $rsvpEventIds)
            ->with(['group'])
            ->orderBy('starts_at')
            ->limit(10)
            ->get();

        // Events in interest-matching groups within location radius
        $interestEvents = collect();
        $userInterests = $user->tagsWithType('interest')->pluck('name')->toArray();

        if ($userInterests && $user->latitude && $user->longitude) {
            $excludeIds = array_merge($rsvpEventIds, $groupEvents->pluck('id')->toArray());

            $interestEvents = Event::query()
                ->upcoming()
                ->whereNotIn('events.id', $excludeIds)
                ->whereNotIn('group_id', $userGroupIds)
                ->whereHas('group', function (Builder $q) use ($userInterests): void {
                    $q->withAnyTags($userInterests, 'interest');
                })
                ->nearby($user->latitude, $user->longitude, 50)
                ->with(['group'])
                ->orderBy('starts_at')
                ->limit(10)
                ->get();
        }

        return $groupEvents->merge($interestEvents)
            ->sortBy('starts_at')
            ->take(10)
            ->values();
    }
}
