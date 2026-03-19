<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user']);

    Route::middleware(['web', 'auth', 'groupMember'])->get('test-group/{group}', function () {
        return response('OK');
    });
});

it('returns 403 when user is not a member of the group', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create();

    $response = $this->actingAs($user)->get("test-group/{$group->id}");

    $response->assertForbidden();
});

it('allows access when user is a member of the group', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create();

    $group->members()->attach($user, ['role' => 'member', 'joined_at' => now()]);

    $response = $this->actingAs($user)->get("test-group/{$group->id}");

    $response->assertOk();
    $response->assertSee('OK');
});

it('returns 403 for a different group the user is not a member of', function () {
    $user = User::factory()->create();
    $memberGroup = Group::factory()->create();
    $otherGroup = Group::factory()->create();

    $memberGroup->members()->attach($user, ['role' => 'member', 'joined_at' => now()]);

    $response = $this->actingAs($user)->get("test-group/{$otherGroup->id}");

    $response->assertForbidden();
});
