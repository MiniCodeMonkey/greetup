<?php

namespace App\Livewire;

use App\Enums\EventType;
use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Services\GeocodingService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\Tags\Tag;

#[Layout('components.layouts.app', ['title' => 'Explore Events', 'description' => 'Discover local meetups, events, and community groups near you.'])]
class ExplorePage extends Component
{
    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $topic = '';

    #[Url(except: '')]
    public string $dateRange = '';

    #[Url(except: '')]
    public string $eventType = '';

    #[Url(except: 50)]
    public int $distance = 50;

    public ?float $latitude = null;

    public ?float $longitude = null;

    public string $locationName = '';

    public int $page = 1;

    public int $perPage = 12;

    public bool $hasMorePages = true;

    public bool $showLocationPrompt = false;

    public function mount(): void
    {
        $user = Auth::user();

        if ($user && $user->latitude && $user->longitude) {
            $this->latitude = (float) $user->latitude;
            $this->longitude = (float) $user->longitude;
            $this->locationName = $user->location ?? '';
        } elseif ($user) {
            $this->showLocationPrompt = true;
        }
    }

    public function setLocation(float $lat, float $lng): void
    {
        $this->latitude = $lat;
        $this->longitude = $lng;

        $geocoding = app(GeocodingService::class);
        $address = $geocoding->reverse($lat, $lng);

        if ($address) {
            $this->locationName = $address;
        }

        $this->resetPage();
    }

    public function loadMore(): void
    {
        $this->page++;
    }

    /**
     * @param  'search'|'topic'|'dateRange'|'eventType'|'distance'  $property
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'topic', 'dateRange', 'eventType', 'distance'])) {
            $this->resetPage();
        }
    }

    public function resetPage(): void
    {
        $this->page = 1;
        $this->hasMorePages = true;
    }

    public function render(): View
    {
        $totalLimit = $this->page * $this->perPage;

        $events = $this->getEvents($totalLimit + 1);

        $this->hasMorePages = $events->count() > $totalLimit;
        $events = $events->take($totalLimit);

        $onlineEvents = $this->getOnlineEvents();

        $topics = Tag::getWithType('interest')->pluck('name')->sort()->values();

        $siteName = config('app.name', 'Greetup');

        return view('livewire.explore-page', [
            'events' => $events,
            'onlineEvents' => $onlineEvents,
            'topics' => $topics,
        ])->layoutData([
            'title' => "Explore Events — {$siteName}",
            'description' => 'Discover local meetups, events, and community groups near you.',
        ]);
    }

    /**
     * @return Collection<int, Event>
     */
    private function getEvents(int $limit): Collection
    {
        $user = Auth::user();

        if ($user && $this->latitude && $this->longitude) {
            return $this->getAuthenticatedWithLocationEvents($user, $limit);
        }

        if ($user) {
            return $this->getAuthenticatedWithoutLocationEvents($user, $limit);
        }

        if ($this->latitude && $this->longitude) {
            return $this->getNearbyGuestEvents($limit);
        }

        return $this->getPopularEvents($limit);
    }

    /**
     * @return Collection<int, Event>
     */
    private function getAuthenticatedWithLocationEvents(mixed $user, int $limit): Collection
    {
        $baseQuery = $this->baseEventQuery()
            ->where('event_type', '!=', EventType::Online);

        $userInterests = $user->tagsWithType('interest')->pluck('name')->toArray();
        $userGroupIds = $user->groups()->pluck('groups.id')->toArray();

        $nearbyMatchingInterests = (clone $baseQuery)
            ->nearby($this->latitude, $this->longitude, $this->distance)
            ->when($userInterests, function (Builder $query) use ($userInterests): void {
                $query->whereHas('group', function (Builder $q) use ($userInterests): void {
                    $q->withAnyTags($userInterests, 'interest');
                });
            })
            ->withCount(['rsvps' => fn (Builder $q) => $q->where('status', RsvpStatus::Going)])
            ->orderByDesc('rsvps_count')
            ->limit($limit)
            ->get();

        if ($nearbyMatchingInterests->count() >= $limit) {
            return $nearbyMatchingInterests;
        }

        $excludeIds = $nearbyMatchingInterests->pluck('id')->toArray();
        $remaining = $limit - $nearbyMatchingInterests->count();

        $rsvpedEventIds = $user->rsvps()
            ->where('status', RsvpStatus::Going)
            ->pluck('event_id')
            ->toArray();

        $groupEvents = (clone $baseQuery)
            ->whereIn('group_id', $userGroupIds)
            ->whereNotIn('id', array_merge($excludeIds, $rsvpedEventIds))
            ->withCount(['rsvps' => fn (Builder $q) => $q->where('status', RsvpStatus::Going)])
            ->orderBy('starts_at')
            ->limit($remaining)
            ->get();

        $combined = $nearbyMatchingInterests->merge($groupEvents);

        if ($combined->count() >= $limit) {
            return $combined;
        }

        $excludeIds = $combined->pluck('id')->toArray();
        $remaining = $limit - $combined->count();

        $popular = $this->baseEventQuery()
            ->where('event_type', '!=', EventType::Online)
            ->whereNotIn('id', $excludeIds)
            ->withCount(['rsvps' => fn (Builder $q) => $q->where('status', RsvpStatus::Going)])
            ->orderByDesc('rsvps_count')
            ->limit($remaining)
            ->get();

        return $combined->merge($popular);
    }

    /**
     * @return Collection<int, Event>
     */
    private function getAuthenticatedWithoutLocationEvents(mixed $user, int $limit): Collection
    {
        $baseQuery = $this->baseEventQuery()
            ->where('event_type', '!=', EventType::Online);

        $userGroupIds = $user->groups()->pluck('groups.id')->toArray();

        $groupEvents = (clone $baseQuery)
            ->whereIn('group_id', $userGroupIds)
            ->withCount(['rsvps' => fn (Builder $q) => $q->where('status', RsvpStatus::Going)])
            ->orderBy('starts_at')
            ->limit($limit)
            ->get();

        if ($groupEvents->count() >= $limit) {
            return $groupEvents;
        }

        $excludeIds = $groupEvents->pluck('id')->toArray();
        $remaining = $limit - $groupEvents->count();

        $popular = $this->baseEventQuery()
            ->where('event_type', '!=', EventType::Online)
            ->whereNotIn('id', $excludeIds)
            ->withCount(['rsvps' => fn (Builder $q) => $q->where('status', RsvpStatus::Going)])
            ->orderByDesc('rsvps_count')
            ->limit($remaining)
            ->get();

        return $groupEvents->merge($popular);
    }

    /**
     * @return Collection<int, Event>
     */
    private function getNearbyGuestEvents(int $limit): Collection
    {
        return $this->baseEventQuery()
            ->where('event_type', '!=', EventType::Online)
            ->nearby($this->latitude, $this->longitude, $this->distance)
            ->withCount(['rsvps' => fn (Builder $q) => $q->where('status', RsvpStatus::Going)])
            ->orderByDesc('rsvps_count')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Event>
     */
    private function getPopularEvents(int $limit): Collection
    {
        return $this->baseEventQuery()
            ->where('event_type', '!=', EventType::Online)
            ->withCount(['rsvps' => fn (Builder $q) => $q->where('status', RsvpStatus::Going)])
            ->orderByDesc('rsvps_count')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Event>
     */
    private function getOnlineEvents(): Collection
    {
        return $this->baseEventQuery()
            ->where('event_type', EventType::Online)
            ->withCount(['rsvps' => fn (Builder $q) => $q->where('status', RsvpStatus::Going)])
            ->orderByDesc('rsvps_count')
            ->limit(6)
            ->get();
    }

    /**
     * @return Builder<Event>
     */
    private function baseEventQuery(): Builder
    {
        $query = Event::query()
            ->upcoming()
            ->with(['group', 'rsvps.user'])
            ->when($this->search, function (Builder $q): void {
                $q->where(function (Builder $sub): void {
                    $sub->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%')
                        ->orWhereHas('group', function (Builder $gq): void {
                            $gq->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->topic, function (Builder $q): void {
                $q->whereHas('group', function (Builder $gq): void {
                    $gq->withAnyTags([$this->topic], 'interest');
                });
            })
            ->when($this->eventType, function (Builder $q): void {
                $q->where('event_type', $this->eventType);
            })
            ->when($this->dateRange, function (Builder $q): void {
                match ($this->dateRange) {
                    'today' => $q->whereDate('starts_at', today()),
                    'tomorrow' => $q->whereDate('starts_at', today()->addDay()),
                    'this_week' => $q->whereBetween('starts_at', [now(), now()->endOfWeek()]),
                    'this_month' => $q->whereBetween('starts_at', [now(), now()->endOfMonth()]),
                    default => null,
                };
            });

        return $query;
    }
}
