<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Group;
use App\Models\Setting;
use App\Models\User;
use App\Services\GeocodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

// ── greetup:install ──────────────────────────────────────────────

it('creates admin user and sets site name in non-interactive mode', function (): void {
    $this->artisan('greetup:install --no-interaction')
        ->assertSuccessful();

    $admin = User::where('email', 'admin@example.com')->first();
    expect($admin)->not->toBeNull()
        ->and($admin->hasRole('admin'))->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull();

    expect(Setting::get('site_name'))->toBe('Greetup');
});

it('creates roles if they do not exist', function (): void {
    Role::query()->delete();

    $this->artisan('greetup:install --no-interaction')
        ->assertSuccessful();

    expect(Role::where('name', 'user')->exists())->toBeTrue()
        ->and(Role::where('name', 'admin')->exists())->toBeTrue();
});

// ── greetup:geocode-missing ──────────────────────────────────────

it('fails when no geocodio API key is configured', function (): void {
    config(['services.geocodio.api_key' => null]);

    $this->artisan('greetup:geocode-missing')
        ->assertFailed();
});

it('geocodes groups with addresses but missing coordinates', function (): void {
    config(['services.geocodio.api_key' => 'test-key']);

    $group = Group::factory()->create([
        'location' => '123 Main St, Denver, CO',
        'latitude' => null,
        'longitude' => null,
    ]);

    $mockGeocoding = Mockery::mock(GeocodingService::class);
    $mockGeocoding->shouldReceive('geocode')
        ->with('123 Main St, Denver, CO')
        ->andReturn(['lat' => 39.7392, 'lng' => -104.9903, 'formatted_address' => '123 Main St, Denver, CO 80202']);

    app()->instance(GeocodingService::class, $mockGeocoding);

    $this->artisan('greetup:geocode-missing')
        ->assertSuccessful();

    $group->refresh();
    expect((float) $group->latitude)->toBe(39.7392)
        ->and((float) $group->longitude)->toBe(-104.9903);
});

it('geocodes events with venue addresses but missing coordinates', function (): void {
    config(['services.geocodio.api_key' => 'test-key']);

    $event = Event::factory()->published()->create([
        'venue_address' => '456 Oak Ave, Boulder, CO',
        'venue_latitude' => null,
        'venue_longitude' => null,
    ]);

    $mockGeocoding = Mockery::mock(GeocodingService::class);
    $mockGeocoding->shouldReceive('geocode')
        ->with('456 Oak Ave, Boulder, CO')
        ->andReturn(['lat' => 40.0150, 'lng' => -105.2705, 'formatted_address' => '456 Oak Ave, Boulder, CO 80302']);

    app()->instance(GeocodingService::class, $mockGeocoding);

    $this->artisan('greetup:geocode-missing')
        ->assertSuccessful();

    $event->refresh();
    expect((float) $event->venue_latitude)->toBe(40.0150)
        ->and((float) $event->venue_longitude)->toBe(-105.2705);
});

it('geocodes users with locations but missing coordinates', function (): void {
    config(['services.geocodio.api_key' => 'test-key']);

    $user = User::factory()->create([
        'location' => '789 Pine Rd, Fort Collins, CO',
        'latitude' => null,
        'longitude' => null,
    ]);

    $mockGeocoding = Mockery::mock(GeocodingService::class);
    $mockGeocoding->shouldReceive('geocode')
        ->with('789 Pine Rd, Fort Collins, CO')
        ->andReturn(['lat' => 40.5853, 'lng' => -105.0844, 'formatted_address' => '789 Pine Rd, Fort Collins, CO 80521']);

    app()->instance(GeocodingService::class, $mockGeocoding);

    $this->artisan('greetup:geocode-missing')
        ->assertSuccessful();

    $user->refresh();
    expect((float) $user->latitude)->toBe(40.5853)
        ->and((float) $user->longitude)->toBe(-105.0844);
});

it('skips records that already have coordinates', function (): void {
    config(['services.geocodio.api_key' => 'test-key']);

    Group::factory()->create([
        'location' => '123 Main St',
        'latitude' => 39.7392,
        'longitude' => -104.9903,
    ]);

    $mockGeocoding = Mockery::mock(GeocodingService::class);
    $mockGeocoding->shouldNotReceive('geocode');

    app()->instance(GeocodingService::class, $mockGeocoding);

    $this->artisan('greetup:geocode-missing')
        ->assertSuccessful();
});

// ── greetup:stats ────────────────────────────────────────────────

it('displays platform statistics', function (): void {
    User::factory()->count(3)->create();
    Group::factory()->count(2)->create();
    Event::factory()->published()->create([
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHours(2),
    ]);

    $this->artisan('greetup:stats')
        ->assertSuccessful();
});

it('counts active events this month correctly', function (): void {
    // Event this month
    Event::factory()->published()->create([
        'starts_at' => now()->startOfMonth()->addDays(5),
        'ends_at' => now()->startOfMonth()->addDays(5)->addHours(2),
    ]);

    // Event next month (should not count)
    Event::factory()->published()->create([
        'starts_at' => now()->addMonth()->startOfMonth()->addDay(),
        'ends_at' => now()->addMonth()->startOfMonth()->addDay()->addHours(2),
    ]);

    $thisMonthCount = Event::query()
        ->where('status', EventStatus::Published)
        ->where('starts_at', '>=', now()->startOfMonth())
        ->where('starts_at', '<=', now()->endOfMonth())
        ->count();

    expect($thisMonthCount)->toBe(1);

    $this->artisan('greetup:stats')
        ->assertSuccessful();
});
