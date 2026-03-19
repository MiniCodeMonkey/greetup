<?php

use App\Enums\NotificationChannel;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('can be created using the factory', function (): void {
    $preference = NotificationPreference::factory()->create();

    expect($preference)->toBeInstanceOf(NotificationPreference::class)
        ->and($preference->exists)->toBeTrue();
});

it('has user belongsTo relationship', function (): void {
    $preference = NotificationPreference::factory()->create();

    expect($preference->user())->toBeInstanceOf(BelongsTo::class)
        ->and($preference->user)->toBeInstanceOf(User::class);
});

it('casts channel to NotificationChannel enum', function (): void {
    $preference = NotificationPreference::factory()->create(['channel' => 'email']);

    expect($preference->channel)->toBeInstanceOf(NotificationChannel::class)
        ->and($preference->channel)->toBe(NotificationChannel::Email);
});

it('casts enabled to boolean', function (): void {
    $preference = NotificationPreference::factory()->create(['enabled' => true]);

    expect($preference->enabled)->toBeBool()
        ->and($preference->enabled)->toBeTrue();
});

it('defaults enabled to true', function (): void {
    $preference = NotificationPreference::factory()->create();

    expect($preference->enabled)->toBeTrue();
});

it('can be created as disabled', function (): void {
    $preference = NotificationPreference::factory()->disabled()->create();

    expect($preference->enabled)->toBeFalse();
});

it('enforces unique constraint on user_id, channel, and type', function (): void {
    $user = User::factory()->create();

    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'channel' => NotificationChannel::Email,
        'type' => 'App\\Notifications\\TestNotification',
    ]);

    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'channel' => NotificationChannel::Email,
        'type' => 'App\\Notifications\\TestNotification',
    ]);
})->throws(UniqueConstraintViolationException::class);
