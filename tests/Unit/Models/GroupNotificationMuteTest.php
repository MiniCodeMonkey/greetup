<?php

use App\Models\Group;
use App\Models\GroupNotificationMute;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('can be created using the factory', function (): void {
    $mute = GroupNotificationMute::factory()->create();

    expect($mute)->toBeInstanceOf(GroupNotificationMute::class)
        ->and($mute->exists)->toBeTrue();
});

it('has user belongsTo relationship', function (): void {
    $mute = GroupNotificationMute::factory()->create();

    expect($mute->user())->toBeInstanceOf(BelongsTo::class)
        ->and($mute->user)->toBeInstanceOf(User::class);
});

it('has group belongsTo relationship', function (): void {
    $mute = GroupNotificationMute::factory()->create();

    expect($mute->group())->toBeInstanceOf(BelongsTo::class)
        ->and($mute->group)->toBeInstanceOf(Group::class);
});

it('enforces unique constraint on user_id and group_id', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create();

    GroupNotificationMute::factory()->create([
        'user_id' => $user->id,
        'group_id' => $group->id,
    ]);

    GroupNotificationMute::factory()->create([
        'user_id' => $user->id,
        'group_id' => $group->id,
    ]);
})->throws(UniqueConstraintViolationException::class);

it('casts created_at to datetime', function (): void {
    $mute = GroupNotificationMute::factory()->create();
    $mute->refresh();

    expect($mute->created_at)->toBeInstanceOf(Carbon::class);
});
