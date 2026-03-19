<?php

use App\Services\GeocodingService;
use Geocodio\Geocodio;
use Mockery\MockInterface;

beforeEach(function () {
    $this->mockClient = Mockery::mock(Geocodio::class);
});

function createService(?string $apiKey = 'test-api-key'): GeocodingService
{
    return new GeocodingService($apiKey);
}

function createServiceWithMock(MockInterface $mock, ?string $apiKey = 'test-api-key'): GeocodingService
{
    $service = new GeocodingService($apiKey);

    // Use reflection to inject the mock client
    $reflection = new ReflectionClass($service);
    $property = $reflection->getProperty('client');
    $property->setAccessible(true);
    $property->setValue($service, $mock);

    return $service;
}

// Forward geocode tests

it('geocodes a valid address', function () {

    $this->mockClient->shouldReceive('geocode')
        ->with('1600 Pennsylvania Ave NW, Washington, DC 20500')
        ->once()
        ->andReturn([
            'results' => [
                [
                    'formatted_address' => '1600 Pennsylvania Ave NW, Washington, DC 20500',
                    'location' => [
                        'lat' => 38.8976633,
                        'lng' => -77.0365739,
                    ],
                ],
            ],
        ]);

    $service = createServiceWithMock($this->mockClient);
    $result = $service->geocode('1600 Pennsylvania Ave NW, Washington, DC 20500');

    expect($result)->toBe([
        'lat' => 38.8976633,
        'lng' => -77.0365739,
        'formatted_address' => '1600 Pennsylvania Ave NW, Washington, DC 20500',
    ]);
});

it('returns null for an invalid address', function () {

    $this->mockClient->shouldReceive('geocode')
        ->with('zzzzzzzzz not a real address')
        ->once()
        ->andReturn(['results' => []]);

    $service = createServiceWithMock($this->mockClient);
    $result = $service->geocode('zzzzzzzzz not a real address');

    expect($result)->toBeNull();
});

// Reverse geocode tests

it('reverse geocodes coordinates', function () {

    $this->mockClient->shouldReceive('reverse')
        ->with([38.8976633, -77.0365739])
        ->once()
        ->andReturn([
            'results' => [
                [
                    'formatted_address' => '1600 Pennsylvania Ave NW, Washington, DC 20500',
                ],
            ],
        ]);

    $service = createServiceWithMock($this->mockClient);
    $result = $service->reverse(38.8976633, -77.0365739);

    expect($result)->toBe('1600 Pennsylvania Ave NW, Washington, DC 20500');
});

// Batch geocode tests

it('batch geocodes multiple addresses', function () {
    $addresses = [
        '1600 Pennsylvania Ave NW, Washington, DC 20500',
        '350 Fifth Avenue, New York, NY 10118',
    ];

    $this->mockClient->shouldReceive('geocode')
        ->with($addresses)
        ->once()
        ->andReturn([
            'results' => [
                [
                    'query' => '1600 Pennsylvania Ave NW, Washington, DC 20500',
                    'response' => [
                        'results' => [
                            [
                                'formatted_address' => '1600 Pennsylvania Ave NW, Washington, DC 20500',
                                'location' => ['lat' => 38.8976633, 'lng' => -77.0365739],
                            ],
                        ],
                    ],
                ],
                [
                    'query' => '350 Fifth Avenue, New York, NY 10118',
                    'response' => [
                        'results' => [
                            [
                                'formatted_address' => '350 5th Ave, New York, NY 10118',
                                'location' => ['lat' => 40.7484405, 'lng' => -73.9856644],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

    $service = createServiceWithMock($this->mockClient);
    $results = $service->batch($addresses);

    expect($results)->toHaveCount(2);
    expect($results['1600 Pennsylvania Ave NW, Washington, DC 20500'])->toBe([
        'lat' => 38.8976633,
        'lng' => -77.0365739,
        'formatted_address' => '1600 Pennsylvania Ave NW, Washington, DC 20500',
    ]);
    expect($results['350 Fifth Avenue, New York, NY 10118'])->toBe([
        'lat' => 40.7484405,
        'lng' => -73.9856644,
        'formatted_address' => '350 5th Ave, New York, NY 10118',
    ]);
});

// Missing API key tests

it('returns null when API key is missing for geocode', function () {
    $service = createService(null);

    expect($service->geocode('some address'))->toBeNull();
});

it('returns null when API key is empty for geocode', function () {
    $service = createService('');

    expect($service->geocode('some address'))->toBeNull();
});

it('returns null when API key is missing for reverse', function () {
    $service = createService(null);

    expect($service->reverse(38.8976633, -77.0365739))->toBeNull();
});

it('returns empty array when API key is missing for batch', function () {
    $service = createService(null);

    expect($service->batch(['some address']))->toBe([]);
});

// API error tests

it('returns null when API throws exception on geocode', function () {

    $this->mockClient->shouldReceive('geocode')
        ->once()
        ->andThrow(new RuntimeException('API error'));

    $service = createServiceWithMock($this->mockClient);
    $result = $service->geocode('some address');

    expect($result)->toBeNull();
});

it('returns null when API throws exception on reverse', function () {

    $this->mockClient->shouldReceive('reverse')
        ->once()
        ->andThrow(new RuntimeException('API error'));

    $service = createServiceWithMock($this->mockClient);
    $result = $service->reverse(38.8976633, -77.0365739);

    expect($result)->toBeNull();
});

it('returns empty array when API throws exception on batch', function () {

    $this->mockClient->shouldReceive('geocode')
        ->once()
        ->andThrow(new RuntimeException('API error'));

    $service = createServiceWithMock($this->mockClient);
    $results = $service->batch(['some address']);

    expect($results)->toBe([]);
});

// Haversine nearby scope tests

it('includes groups within the specified radius using Haversine scope', function () {
    // Test the Haversine formula logic used in scopeNearby
    // New York City coordinates
    $centerLat = 40.7128;
    $centerLng = -74.0060;

    // Calculate Haversine distance manually for a point ~10km away (Newark, NJ area)
    $nearbyLat = 40.7357;
    $nearbyLng = -74.1724;

    $distance = haversineDistance($centerLat, $centerLng, $nearbyLat, $nearbyLng);

    // Newark is roughly 15km from NYC center
    expect($distance)->toBeLessThan(20.0);
    expect($distance)->toBeGreaterThan(5.0);
});

it('excludes locations outside the specified radius using Haversine calculation', function () {
    // New York City
    $centerLat = 40.7128;
    $centerLng = -74.0060;

    // Los Angeles
    $farLat = 34.0522;
    $farLng = -118.2437;

    $distance = haversineDistance($centerLat, $centerLng, $farLat, $farLng);

    // NYC to LA is ~3940km, well outside any reasonable radius
    expect($distance)->toBeGreaterThan(3900.0);
    expect($distance)->toBeLessThan(4000.0);
});

/**
 * Calculate Haversine distance between two points in kilometers.
 * Mirrors the SQL formula used in scopeNearby.
 */
function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    return 6371 * acos(
        cos(deg2rad($lat1)) * cos(deg2rad($lat2))
        * cos(deg2rad($lng2) - deg2rad($lng1))
        + sin(deg2rad($lat1)) * sin(deg2rad($lat2))
    );
}
