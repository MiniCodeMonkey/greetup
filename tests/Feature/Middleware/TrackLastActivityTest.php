<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user']);
});

it('updates last_active_at on authenticated request', function () {
    $user = User::factory()->create(['last_active_at' => null]);

    $this->actingAs($user)->get('/dashboard');

    $user->refresh();
    expect($user->last_active_at)->not->toBeNull();
});

it('does not fail on unauthenticated request', function () {
    $response = $this->get('/');

    $response->assertRedirect('/explore');
});

it('updates last_active_at to current time', function () {
    $this->freezeTime();
    $user = User::factory()->create(['last_active_at' => now()->subDay()]);

    $this->actingAs($user)->get('/dashboard');

    $user->refresh();
    expect($user->last_active_at->toDateTimeString())->toBe(now()->toDateTimeString());
});
