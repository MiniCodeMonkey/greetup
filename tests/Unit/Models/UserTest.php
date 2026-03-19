<?php

use App\Enums\ProfileVisibility;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

it('can be created using the factory', function (): void {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->exists)->toBeTrue()
        ->and($user->name)->toBeString()
        ->and($user->email)->toBeString()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->location)->toBeString()
        ->and($user->latitude)->not->toBeNull()
        ->and($user->longitude)->not->toBeNull()
        ->and($user->timezone)->toBeString()
        ->and($user->looking_for)->toBeArray()
        ->and($user->profile_visibility)->toBe(ProfileVisibility::Public);
});

it('assigns 3-8 random interests via factory', function (): void {
    $user = User::factory()->create();

    $interests = $user->tags()->where('type', 'interest')->get();

    expect($interests->count())->toBeGreaterThanOrEqual(3)
        ->and($interests->count())->toBeLessThanOrEqual(8);
});

it('has groups belongsToMany relationship', function (): void {
    $user = User::factory()->create();

    expect($user->groups())->toBeInstanceOf(BelongsToMany::class);
});

it('has organizedGroups hasMany relationship', function (): void {
    $user = User::factory()->create();

    expect($user->organizedGroups())->toBeInstanceOf(HasMany::class);
});

it('has rsvps hasMany relationship', function (): void {
    $user = User::factory()->create();

    expect($user->rsvps())->toBeInstanceOf(HasMany::class);
});

it('has discussions hasMany relationship', function (): void {
    $user = User::factory()->create();

    expect($user->discussions())->toBeInstanceOf(HasMany::class);
});

it('has blocks hasMany relationship', function (): void {
    $user = User::factory()->create();

    expect($user->blocks())->toBeInstanceOf(HasMany::class);
});

it('casts looking_for as array', function (): void {
    $user = User::factory()->create([
        'looking_for' => ['making friends', 'networking'],
    ]);

    $user->refresh();

    expect($user->looking_for)->toBeArray()
        ->and($user->looking_for)->toBe(['making friends', 'networking']);
});

it('casts profile_visibility as ProfileVisibility enum', function (): void {
    $user = User::factory()->create([
        'profile_visibility' => ProfileVisibility::MembersOnly,
    ]);

    $user->refresh();

    expect($user->profile_visibility)->toBe(ProfileVisibility::MembersOnly);
});

it('casts is_suspended as boolean', function (): void {
    $user = User::factory()->create(['is_suspended' => true]);

    $user->refresh();

    expect($user->is_suspended)->toBeTrue();
});

it('casts date fields as Carbon instances', function (): void {
    $user = User::factory()->create([
        'last_active_at' => now(),
        'suspended_at' => now(),
    ]);

    $user->refresh();

    expect($user->email_verified_at)->toBeInstanceOf(Carbon::class)
        ->and($user->last_active_at)->toBeInstanceOf(Carbon::class)
        ->and($user->suspended_at)->toBeInstanceOf(Carbon::class);
});

it('uses soft deletes', function (): void {
    expect(in_array(SoftDeletes::class, class_uses_recursive(User::class)))->toBeTrue();

    $user = User::factory()->create();
    $user->delete();

    expect(User::withTrashed()->find($user->id))->not->toBeNull()
        ->and(User::find($user->id))->toBeNull()
        ->and($user->deleted_at)->not->toBeNull();
});

it('implements HasMedia interface', function (): void {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(HasMedia::class);
});

it('registers avatar media collection', function (): void {
    $user = User::factory()->create();

    $collections = $user->getRegisteredMediaCollections();
    $avatarCollection = $collections->firstWhere('name', 'avatar');

    expect($avatarCollection)->not->toBeNull()
        ->and($avatarCollection->singleFile)->toBeTrue();
});

it('registers media conversions for avatar', function (): void {
    $user = User::factory()->create();

    $user->registerMediaConversions(null);

    $reflection = new ReflectionProperty($user, 'mediaConversions');
    $conversions = $reflection->getValue($user);

    $conversionNames = array_map(fn ($c) => $c->getName(), $conversions);

    expect($conversionNames)->toContain('nav')
        ->and($conversionNames)->toContain('profile-card')
        ->and($conversionNames)->toContain('profile-page');
});

it('returns correct searchable array', function (): void {
    $user = User::factory()->create([
        'bio' => 'A software developer from Copenhagen.',
    ]);

    $searchable = $user->toSearchableArray();

    expect($searchable)->toHaveKeys(['id', 'name', 'bio'])
        ->and($searchable['id'])->toBe($user->id)
        ->and($searchable['name'])->toBe($user->name)
        ->and($searchable['bio'])->toBe('A software developer from Copenhagen.');
});

it('is searchable only when profile_visibility is public', function (): void {
    $publicUser = User::factory()->create([
        'profile_visibility' => ProfileVisibility::Public,
    ]);

    $membersOnlyUser = User::factory()->create([
        'profile_visibility' => ProfileVisibility::MembersOnly,
    ]);

    expect($publicUser->shouldBeSearchable())->toBeTrue()
        ->and($membersOnlyUser->shouldBeSearchable())->toBeFalse();
});

it('creates an unverified user via factory state', function (): void {
    $user = User::factory()->unverified()->create();

    expect($user->email_verified_at)->toBeNull();
});

it('creates a suspended user via factory state', function (): void {
    $user = User::factory()->suspended()->create();

    expect($user->is_suspended)->toBeTrue()
        ->and($user->suspended_at)->not->toBeNull()
        ->and($user->suspended_reason)->toBe('Violation of community guidelines');
});

it('creates an admin user via factory state', function (): void {
    $user = User::factory()->admin()->create();

    expect($user->hasRole('admin'))->toBeTrue();
});

it('uses locations from the predefined set', function (): void {
    $validCities = [
        'Copenhagen, Denmark',
        'Berlin, Germany',
        'London, United Kingdom',
        'New York, NY',
    ];

    $user = User::factory()->create();

    expect($validCities)->toContain($user->location);
});
