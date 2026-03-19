<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user']);

    Route::middleware(['web', 'auth', 'groupRole:event_organizer'])->get('test-role/{group}', function () {
        return response('OK');
    });
});

it('returns 403 when user is not a member of the group', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create();

    $response = $this->actingAs($user)->get("test-role/{$group->id}");

    $response->assertForbidden();
});

it('returns 403 when member role is below required level', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create();

    $group->members()->attach($user, ['role' => 'member', 'joined_at' => now()]);

    $response = $this->actingAs($user)->get("test-role/{$group->id}");

    $response->assertForbidden();
});

it('allows access when member role matches required level', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create();

    $group->members()->attach($user, ['role' => 'event_organizer', 'joined_at' => now()]);

    $response = $this->actingAs($user)->get("test-role/{$group->id}");

    $response->assertOk();
    $response->assertSee('OK');
});

it('allows access when member role exceeds required level', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create();

    $group->members()->attach($user, ['role' => 'organizer', 'joined_at' => now()]);

    $response = $this->actingAs($user)->get("test-role/{$group->id}");

    $response->assertOk();
});

it('respects full role hierarchy ordering', function () {
    $group = Group::factory()->create();

    $roles = ['member', 'event_organizer', 'assistant_organizer', 'co_organizer', 'organizer'];

    foreach ($roles as $role) {
        $user = User::factory()->create();
        $group->members()->attach($user, ['role' => $role, 'joined_at' => now()]);

        $response = $this->actingAs($user)->get("test-role/{$group->id}");

        if ($role === 'member') {
            $response->assertForbidden();
        } else {
            $response->assertOk();
        }
    }
});

it('returns 403 for assistant_organizer when organizer role is required', function () {
    Route::middleware(['web', 'auth', 'groupRole:organizer'])->get('test-organizer-only/{group}', function () {
        return response('OK');
    });

    $user = User::factory()->create();
    $group = Group::factory()->create();

    $group->members()->attach($user, ['role' => 'assistant_organizer', 'joined_at' => now()]);

    $response = $this->actingAs($user)->get("test-organizer-only/{$group->id}");

    $response->assertForbidden();
});
