<?php

namespace App\Livewire;

use App\Models\Group;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\Tags\Tag;

#[Layout('components.layouts.app')]
class GroupSearchPage extends Component
{
    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $topic = '';

    #[Url(except: 50)]
    public int $distance = 50;

    #[Url(except: '')]
    public string $sort = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public int $page = 1;

    public int $perPage = 12;

    public bool $hasMorePages = true;

    public function mount(): void
    {
        $user = Auth::user();

        if ($user && $user->latitude && $user->longitude) {
            $this->latitude = (float) $user->latitude;
            $this->longitude = (float) $user->longitude;
        }
    }

    public function loadMore(): void
    {
        $this->page++;
    }

    /**
     * @param  'search'|'topic'|'distance'|'sort'  $property
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'topic', 'distance', 'sort'])) {
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

        $groups = $this->getGroups($totalLimit + 1);

        $this->hasMorePages = $groups->count() > $totalLimit;
        $groups = $groups->take($totalLimit);

        $topics = Tag::getWithType('topic')->pluck('name')->sort()->values();

        $siteName = Setting::get('site_name', config('app.name', 'Greetup'));

        return view('livewire.group-search-page', [
            'groups' => $groups,
            'topics' => $topics,
        ])->layoutData([
            'title' => "Browse Groups — {$siteName}",
            'description' => 'Search and discover community groups to join.',
        ]);
    }

    /**
     * @return Collection<int, Group>
     */
    private function getGroups(int $limit): Collection
    {
        if ($this->search !== '') {
            return $this->getSearchResults($limit);
        }

        return $this->getFilteredGroups($limit);
    }

    /**
     * @return Collection<int, Group>
     */
    private function getSearchResults(int $limit): Collection
    {
        $scoutDriver = config('scout.driver');

        if ($scoutDriver && $scoutDriver !== 'null') {
            return $this->getScoutSearchResults($limit);
        }

        return $this->getLikeSearchResults($limit);
    }

    /**
     * @return Collection<int, Group>
     */
    private function getScoutSearchResults(int $limit): Collection
    {
        $groupIds = Group::search($this->search)
            ->take($limit * 2)
            ->keys()
            ->toArray();

        if (empty($groupIds)) {
            return collect();
        }

        $query = Group::query()
            ->whereIn('id', $groupIds)
            ->active()
            ->public()
            ->with(['organizer'])
            ->withCount(['members', 'events'])
            ->when($this->topic, function (Builder $q): void {
                $q->withAnyTags([$this->topic], 'topic');
            })
            ->when($this->latitude && $this->longitude && $this->distance < 250, function (Builder $q): void {
                $q->nearby($this->latitude, $this->longitude, $this->distance);
            });

        $query = $this->applySortToQuery($query);

        return $query->limit($limit)->get();
    }

    /**
     * @return Collection<int, Group>
     */
    private function getLikeSearchResults(int $limit): Collection
    {
        $query = Group::query()
            ->active()
            ->public()
            ->with(['organizer'])
            ->withCount(['members', 'events'])
            ->where(function (Builder $q): void {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%')
                    ->orWhere('location', 'like', '%'.$this->search.'%');
            })
            ->when($this->topic, function (Builder $q): void {
                $q->withAnyTags([$this->topic], 'topic');
            })
            ->when($this->latitude && $this->longitude && $this->distance < 250, function (Builder $q): void {
                $q->nearby($this->latitude, $this->longitude, $this->distance);
            });

        $query = $this->applySortToQuery($query);

        return $query->limit($limit)->get();
    }

    /**
     * @return Collection<int, Group>
     */
    private function getFilteredGroups(int $limit): Collection
    {
        $query = Group::query()
            ->active()
            ->public()
            ->with(['organizer'])
            ->withCount(['members', 'events'])
            ->when($this->topic, function (Builder $q): void {
                $q->withAnyTags([$this->topic], 'topic');
            })
            ->when($this->latitude && $this->longitude && $this->distance < 250, function (Builder $q): void {
                $q->nearby($this->latitude, $this->longitude, $this->distance);
            });

        $query = $this->applySortToQuery($query);

        return $query->limit($limit)->get();
    }

    /**
     * @param  Builder<Group>  $query
     * @return Builder<Group>
     */
    private function applySortToQuery(Builder $query): Builder
    {
        return match ($this->sort) {
            'newest' => $query->orderByDesc('created_at'),
            'most_members' => $query->orderByDesc('members_count'),
            'most_active' => $query->withCount(['events as recent_events_count' => function (Builder $q): void {
                $q->where('starts_at', '>=', now()->subMonths(3));
            }])->orderByDesc('recent_events_count'),
            default => $query->orderByDesc('created_at'),
        };
    }
}
