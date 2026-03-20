<?php

use App\Enums\EventType;
use App\Enums\GroupRole;
use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('stores a valid avatar upload via medialibrary', function () {
    Storage::fake('local');
    Storage::fake('public');
    $user = User::factory()->create();

    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200)->size(1024);

    $this->actingAs($user)
        ->put('/settings/profile', [
            'name' => $user->name,
            'avatar' => $file,
        ])
        ->assertRedirect(route('settings', ['section' => 'profile']))
        ->assertSessionHas('status');

    $media = $user->fresh()->getFirstMedia('avatar');
    expect($media)->not->toBeNull()
        ->and($media->disk)->toBe('local')
        ->and($media->collection_name)->toBe('avatar');
});

it('returns 422 for oversized avatar upload', function () {
    Storage::fake('local');
    Storage::fake('public');
    $user = User::factory()->create();

    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200)->size(3000);

    $this->actingAs($user)
        ->put('/settings/profile', [
            'name' => $user->name,
            'avatar' => $file,
        ])
        ->assertSessionHasErrors('avatar');
});

it('returns 422 for non-image avatar upload', function () {
    Storage::fake('local');
    Storage::fake('public');
    $user = User::factory()->create();

    $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    $this->actingAs($user)
        ->put('/settings/profile', [
            'name' => $user->name,
            'avatar' => $file,
        ])
        ->assertSessionHasErrors('avatar');
});

it('generates group cover photo conversions', function () {
    Storage::fake('local');
    Storage::fake('public');
    $user = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $user->id]);
    $group->members()->attach($user->id, ['role' => GroupRole::Organizer->value, 'joined_at' => now()]);

    $file = UploadedFile::fake()->image('cover.jpg', 1200, 400)->size(2048);

    $this->actingAs($user)
        ->put(route('groups.manage.settings.update', $group), [
            'name' => $group->name,
            'slug' => $group->slug,
            'visibility' => $group->visibility->value,
            'cover_photo' => $file,
        ])
        ->assertRedirect();

    $media = $group->fresh()->getFirstMedia('cover_photo');
    expect($media)->not->toBeNull()
        ->and($media->disk)->toBe('local')
        ->and($media->hasGeneratedConversion('card'))->toBeTrue()
        ->and($media->hasGeneratedConversion('header'))->toBeTrue();
});

it('generates event cover photo conversions', function () {
    Storage::fake('local');
    Storage::fake('public');
    $user = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $user->id]);
    $group->members()->attach($user->id, ['role' => GroupRole::Organizer->value, 'joined_at' => now()]);

    $file = UploadedFile::fake()->image('event-cover.jpg', 1200, 400)->size(2048);

    $startsAt = now()->addDays(7)->format('Y-m-d\TH:i');

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Test Event',
            'event_type' => EventType::InPerson->value,
            'starts_at' => $startsAt,
            'timezone' => 'America/New_York',
            'venue_name' => 'Test Venue',
            'venue_address' => '123 Test St',
            'cover_photo' => $file,
        ])
        ->assertRedirect();

    $event = Event::where('name', 'Test Event')->first();
    expect($event)->not->toBeNull();

    $media = $event->getFirstMedia('cover_photo');
    expect($media)->not->toBeNull()
        ->and($media->disk)->toBe('local')
        ->and($media->hasGeneratedConversion('card'))->toBeTrue()
        ->and($media->hasGeneratedConversion('header'))->toBeTrue();
});
