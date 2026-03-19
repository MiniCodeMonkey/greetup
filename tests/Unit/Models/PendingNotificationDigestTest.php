<?php

use App\Models\PendingNotificationDigest;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('can be created using the factory', function (): void {
    $digest = PendingNotificationDigest::factory()->create();

    expect($digest)->toBeInstanceOf(PendingNotificationDigest::class)
        ->and($digest->exists)->toBeTrue();
});

it('has user belongsTo relationship', function (): void {
    $digest = PendingNotificationDigest::factory()->create();

    expect($digest->user())->toBeInstanceOf(BelongsTo::class)
        ->and($digest->user)->toBeInstanceOf(User::class);
});

it('casts data to array', function (): void {
    $data = ['message' => 'Test notification', 'count' => 5];
    $digest = PendingNotificationDigest::factory()->create(['data' => $data]);
    $digest->refresh();

    expect($digest->data)->toBeArray()
        ->and($digest->data['message'])->toBe('Test notification')
        ->and($digest->data['count'])->toBe(5);
});

it('casts created_at to datetime', function (): void {
    $digest = PendingNotificationDigest::factory()->create();
    $digest->refresh();

    expect($digest->created_at)->toBeInstanceOf(Carbon::class);
});
