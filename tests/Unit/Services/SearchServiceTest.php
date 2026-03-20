<?php

use App\Services\SearchService;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->service = new SearchService;
});

// --- Field Weights ---

it('defines correct field weights per spec section 8.1', function (): void {
    expect(SearchService::FIELD_WEIGHTS['groups'])->toBe([
        'name' => SearchService::WEIGHT_HIGH,
        'description' => SearchService::WEIGHT_MEDIUM,
        'location' => SearchService::WEIGHT_LOW,
    ])
        ->and(SearchService::FIELD_WEIGHTS['events'])->toBe([
            'name' => SearchService::WEIGHT_HIGH,
            'description' => SearchService::WEIGHT_MEDIUM,
            'venue_name' => SearchService::WEIGHT_LOW,
        ])
        ->and(SearchService::FIELD_WEIGHTS['users'])->toBe([
            'name' => SearchService::WEIGHT_HIGH,
            'bio' => SearchService::WEIGHT_LOW,
        ]);
});

it('has weight constants with correct values', function (): void {
    expect(SearchService::WEIGHT_HIGH)->toBe(3)
        ->and(SearchService::WEIGHT_MEDIUM)->toBe(2)
        ->and(SearchService::WEIGHT_LOW)->toBe(1);
});

// --- Search Methods ---

it('returns all three model types from searchAll', function (): void {
    $results = $this->service->searchAll('test');

    expect($results)->toHaveKeys(['groups', 'events', 'users']);
});

it('returns a collection from searchGroups', function (): void {
    $results = $this->service->searchGroups('test');

    expect($results)->toBeInstanceOf(Collection::class);
});

it('returns a collection from searchEvents', function (): void {
    $results = $this->service->searchEvents('test');

    expect($results)->toBeInstanceOf(Collection::class);
});

it('returns a collection from searchUsers', function (): void {
    $results = $this->service->searchUsers('test');

    expect($results)->toBeInstanceOf(Collection::class);
});
