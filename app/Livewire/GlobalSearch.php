<?php

namespace App\Livewire;

use App\Enums\ProfileVisibility;
use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class GlobalSearch extends Component
{
    #[Url(except: '')]
    public string $query = '';

    /**
     * @return Collection<int, Group>
     */
    private function searchGroups(): Collection
    {
        if ($this->query === '') {
            return collect();
        }

        $scoutDriver = config('scout.driver');

        if ($scoutDriver && $scoutDriver !== 'null') {
            return $this->searchGroupsWithScout();
        }

        return $this->searchGroupsWithLike();
    }

    /**
     * @return Collection<int, Group>
     */
    private function searchGroupsWithScout(): Collection
    {
        $groupIds = Group::search($this->query)
            ->take(10)
            ->keys()
            ->toArray();

        if (empty($groupIds)) {
            return collect();
        }

        return Group::query()
            ->whereIn('id', $groupIds)
            ->active()
            ->public()
            ->with(['organizer'])
            ->withCount(['members'])
            ->limit(5)
            ->get();
    }

    /**
     * @return Collection<int, Group>
     */
    private function searchGroupsWithLike(): Collection
    {
        return Group::query()
            ->active()
            ->public()
            ->with(['organizer'])
            ->withCount(['members'])
            ->where(function ($q): void {
                $q->where('name', 'like', '%'.$this->query.'%')
                    ->orWhere('description', 'like', '%'.$this->query.'%');
            })
            ->limit(5)
            ->get();
    }

    /**
     * @return Collection<int, Event>
     */
    private function searchEvents(): Collection
    {
        if ($this->query === '') {
            return collect();
        }

        $scoutDriver = config('scout.driver');

        if ($scoutDriver && $scoutDriver !== 'null') {
            return $this->searchEventsWithScout();
        }

        return $this->searchEventsWithLike();
    }

    /**
     * @return Collection<int, Event>
     */
    private function searchEventsWithScout(): Collection
    {
        $eventIds = Event::search($this->query)
            ->take(10)
            ->keys()
            ->toArray();

        if (empty($eventIds)) {
            return collect();
        }

        return Event::query()
            ->whereIn('id', $eventIds)
            ->published()
            ->with(['group'])
            ->limit(5)
            ->get();
    }

    /**
     * @return Collection<int, Event>
     */
    private function searchEventsWithLike(): Collection
    {
        return Event::query()
            ->published()
            ->with(['group'])
            ->where(function ($q): void {
                $q->where('name', 'like', '%'.$this->query.'%')
                    ->orWhere('description', 'like', '%'.$this->query.'%');
            })
            ->limit(5)
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function searchUsers(): Collection
    {
        if ($this->query === '') {
            return collect();
        }

        $scoutDriver = config('scout.driver');

        if ($scoutDriver && $scoutDriver !== 'null') {
            return $this->searchUsersWithScout();
        }

        return $this->searchUsersWithLike();
    }

    /**
     * @return Collection<int, User>
     */
    private function searchUsersWithScout(): Collection
    {
        $userIds = User::search($this->query)
            ->take(10)
            ->keys()
            ->toArray();

        if (empty($userIds)) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->where('profile_visibility', ProfileVisibility::Public)
            ->limit(5)
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function searchUsersWithLike(): Collection
    {
        return User::query()
            ->where('profile_visibility', ProfileVisibility::Public)
            ->where(function ($q): void {
                $q->where('name', 'like', '%'.$this->query.'%')
                    ->orWhere('bio', 'like', '%'.$this->query.'%');
            })
            ->limit(5)
            ->get();
    }

    public function render(): View
    {
        $groups = $this->searchGroups();
        $events = $this->searchEvents();
        $users = $this->searchUsers();

        $siteName = config('app.name', 'Greetup');
        $title = $this->query !== ''
            ? "Search: \"{$this->query}\" — {$siteName}"
            : "Search — {$siteName}";

        return view('livewire.global-search', [
            'groups' => $groups,
            'events' => $events,
            'users' => $users,
        ])->layoutData([
            'title' => $title,
            'description' => 'Search for groups, events, and members.',
        ]);
    }
}
