<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Collection;

class SearchService
{
    /**
     * Field weight constants for search ranking.
     */
    public const WEIGHT_HIGH = 3;

    public const WEIGHT_MEDIUM = 2;

    public const WEIGHT_LOW = 1;

    /**
     * Search field weights per model as defined in spec section 8.1.
     *
     * @var array<string, array<string, int>>
     */
    public const FIELD_WEIGHTS = [
        'groups' => [
            'name' => self::WEIGHT_HIGH,
            'description' => self::WEIGHT_MEDIUM,
            'location' => self::WEIGHT_LOW,
        ],
        'events' => [
            'name' => self::WEIGHT_HIGH,
            'description' => self::WEIGHT_MEDIUM,
            'venue_name' => self::WEIGHT_LOW,
        ],
        'users' => [
            'name' => self::WEIGHT_HIGH,
            'bio' => self::WEIGHT_LOW,
        ],
    ];

    /**
     * Search across all models (Group, Event, User).
     *
     * @return array{groups: Collection<int, Group>, events: Collection<int, Event>, users: Collection<int, User>}
     */
    public function searchAll(string $query, int $perModel = 10): array
    {
        return [
            'groups' => $this->searchGroups($query, $perModel),
            'events' => $this->searchEvents($query, $perModel),
            'users' => $this->searchUsers($query, $perModel),
        ];
    }

    /**
     * Search groups via Scout.
     *
     * @return Collection<int, Group>
     */
    public function searchGroups(string $query, int $limit = 10): Collection
    {
        return Group::search($query)
            ->take($limit)
            ->get();
    }

    /**
     * Search events via Scout.
     *
     * @return Collection<int, Event>
     */
    public function searchEvents(string $query, int $limit = 10): Collection
    {
        return Event::search($query)
            ->take($limit)
            ->get();
    }

    /**
     * Search users via Scout (respects shouldBeSearchable for public profiles only).
     *
     * @return Collection<int, User>
     */
    public function searchUsers(string $query, int $limit = 10): Collection
    {
        return User::search($query)
            ->take($limit)
            ->get();
    }
}
