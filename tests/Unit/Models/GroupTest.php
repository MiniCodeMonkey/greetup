<?php

use App\Enums\GroupVisibility;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('can be created using the factory', function (): void {
    $group = Group::factory()->create();

    expect($group)->toBeInstanceOf(Group::class)
        ->and($group->exists)->toBeTrue()
        ->and($group->name)->toBeString()
        ->and($group->slug)->toBeString()
        ->and($group->description)->toBeString()
        ->and($group->description_html)->toBeString()
        ->and($group->location)->toBeString()
        ->and($group->latitude)->not->toBeNull()
        ->and($group->longitude)->not->toBeNull()
        ->and($group->timezone)->toBeString()
        ->and($group->visibility)->toBe(GroupVisibility::Public)
        ->and($group->requires_approval)->toBeFalse()
        ->and($group->is_active)->toBeTrue();
});

it('has organizer belongsTo relationship', function (): void {
    $group = Group::factory()->create();

    expect($group->organizer())->toBeInstanceOf(BelongsTo::class)
        ->and($group->organizer)->toBeInstanceOf(User::class);
});

it('has members belongsToMany relationship', function (): void {
    $group = Group::factory()->create();

    expect($group->members())->toBeInstanceOf(BelongsToMany::class);
});

it('has events hasMany relationship', function (): void {
    $group = Group::factory()->create();

    expect($group->events())->toBeInstanceOf(HasMany::class);
});

it('has discussions hasMany relationship', function (): void {
    $group = Group::factory()->create();

    expect($group->discussions())->toBeInstanceOf(HasMany::class);
});

it('generates slug from name automatically', function (): void {
    $group = Group::factory()->create(['name' => 'My Awesome Group']);

    expect($group->slug)->toBe('my-awesome-group');
});

it('generates unique slugs for duplicate names', function (): void {
    $group1 = Group::factory()->create(['name' => 'Duplicate Name']);
    $group2 = Group::factory()->create(['name' => 'Duplicate Name']);

    expect($group1->slug)->toBe('duplicate-name')
        ->and($group2->slug)->not->toBe($group1->slug)
        ->and($group2->slug)->toStartWith('duplicate-name');
});

it('casts visibility to GroupVisibility enum', function (): void {
    $group = Group::factory()->create();

    expect($group->visibility)->toBeInstanceOf(GroupVisibility::class);
});

it('casts requires_approval to boolean', function (): void {
    $group = Group::factory()->create(['requires_approval' => true]);

    expect($group->requires_approval)->toBeBool()
        ->and($group->requires_approval)->toBeTrue();
});

it('casts is_active to boolean', function (): void {
    $group = Group::factory()->create(['is_active' => false]);

    expect($group->is_active)->toBeBool()
        ->and($group->is_active)->toBeFalse();
});

it('casts max_members to integer', function (): void {
    $group = Group::factory()->create(['max_members' => 100]);

    expect($group->max_members)->toBeInt()
        ->and($group->max_members)->toBe(100);
});

it('supports soft deletes', function (): void {
    $group = Group::factory()->create();

    expect(in_array(SoftDeletes::class, class_uses_recursive($group)))->toBeTrue();

    $group->delete();

    expect(Group::withTrashed()->find($group->id))->not->toBeNull()
        ->and(Group::find($group->id))->toBeNull();
});

it('implements HasMedia interface', function (): void {
    $group = Group::factory()->create();

    expect($group)->toBeInstanceOf(HasMedia::class);
});

it('registers cover_photo media collection', function (): void {
    $group = Group::factory()->create();
    $group->registerMediaCollections();

    $collections = $group->getRegisteredMediaCollections();
    $coverPhoto = $collections->firstWhere('name', 'cover_photo');

    expect($coverPhoto)->not->toBeNull()
        ->and($coverPhoto->singleFile)->toBeTrue();
});

it('registers card and header media conversions', function (): void {
    $group = Group::factory()->create();
    $group->registerMediaConversions();

    $reflection = new ReflectionProperty($group, 'mediaConversions');
    $conversions = $reflection->getValue($group);

    $conversionNames = array_map(fn ($c) => $c->getName(), $conversions);

    expect($conversionNames)->toContain('card')
        ->and($conversionNames)->toContain('header');
});

it('scopes active groups', function (): void {
    Group::factory()->create(['is_active' => true]);
    Group::factory()->create(['is_active' => false]);

    $activeGroups = Group::active()->get();

    expect($activeGroups)->toHaveCount(1)
        ->and($activeGroups->first()->is_active)->toBeTrue();
});

it('scopes public groups', function (): void {
    Group::factory()->create(['visibility' => GroupVisibility::Public]);
    Group::factory()->private()->create();

    $publicGroups = Group::public()->get();

    expect($publicGroups)->toHaveCount(1)
        ->and($publicGroups->first()->visibility)->toBe(GroupVisibility::Public);
});

it('scopes nearby groups using haversine formula', function (): void {
    // Copenhagen
    $copenhagen = Group::factory()->create([
        'name' => 'Copenhagen Group',
        'latitude' => 55.6761000,
        'longitude' => 12.5683000,
    ]);

    // Berlin (~355 km from Copenhagen)
    Group::factory()->create([
        'name' => 'Berlin Group',
        'latitude' => 52.5200000,
        'longitude' => 13.4050000,
    ]);

    // New York (~6,000 km from Copenhagen)
    Group::factory()->create([
        'name' => 'New York Group',
        'latitude' => 40.7128000,
        'longitude' => -74.0060000,
    ]);

    // Group with no location
    Group::factory()->create([
        'name' => 'No Location Group',
        'latitude' => null,
        'longitude' => null,
    ]);

    // 50km radius from Copenhagen — only Copenhagen group
    $nearby50 = Group::nearby(55.6761, 12.5683, 50)->get();
    expect($nearby50)->toHaveCount(1)
        ->and($nearby50->first()->name)->toBe('Copenhagen Group');

    // 400km radius — Copenhagen and Berlin
    $nearby400 = Group::nearby(55.6761, 12.5683, 400)->get();
    expect($nearby400)->toHaveCount(2);

    // 10000km radius — all groups with coordinates
    $nearby10000 = Group::nearby(55.6761, 12.5683, 10000)->get();
    expect($nearby10000)->toHaveCount(3);
});

it('returns searchable array with correct fields', function (): void {
    $group = Group::factory()->create([
        'name' => 'Test Search Group',
        'description' => 'A test description',
        'location' => 'Berlin, Germany',
    ]);

    $searchable = $group->toSearchableArray();

    expect($searchable)->toHaveKeys(['id', 'name', 'description', 'location'])
        ->and($searchable['name'])->toBe('Test Search Group')
        ->and($searchable['description'])->toBe('A test description')
        ->and($searchable['location'])->toBe('Berlin, Germany');
});

it('has private factory state', function (): void {
    $group = Group::factory()->private()->create();

    expect($group->visibility)->toBe(GroupVisibility::Private);
});

it('has requiresApproval factory state', function (): void {
    $group = Group::factory()->requiresApproval()->create();

    expect($group->requires_approval)->toBeTrue();
});

it('has inactive factory state', function (): void {
    $group = Group::factory()->inactive()->create();

    expect($group->is_active)->toBeFalse();
});

it('attaches members with pivot data', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $group->members()->attach($user->id, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    expect($group->members)->toHaveCount(1)
        ->and($group->members->first()->pivot->role)->toBe('member')
        ->and($group->members->first()->pivot->joined_at)->not->toBeNull();
});

it('uses spatie tags for interests', function (): void {
    $group = Group::factory()->create();

    $group->attachTag('hiking', 'interest');
    $group->attachTag('photography', 'interest');

    $interests = $group->tags()->where('type', 'interest')->get();

    expect($interests)->toHaveCount(2);
});
