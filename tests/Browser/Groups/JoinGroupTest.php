<?php

use App\Models\Group;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;

uses(DatabaseMigrations::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('user can join a public group', function () {
    $organizer = User::factory()->create();
    $group = Group::factory()->create([
        'name' => 'Open Group',
        'organizer_id' => $organizer->id,
    ]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);

    $user = User::factory()->create(['name' => 'New Member']);

    $this->browse(function (Browser $browser) use ($user, $group) {
        $browser->loginAs($user)
            ->visit('/groups/'.$group->slug)
            ->assertSee('Open Group')
            ->click('[data-testid="join-button"]')
            ->waitForText('You have joined');
    });
});

test('user can request to join a group that requires approval', function () {
    $organizer = User::factory()->create();
    $group = Group::factory()->requiresApproval()->create([
        'name' => 'Approval Group',
        'organizer_id' => $organizer->id,
    ]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);

    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user, $group) {
        $browser->loginAs($user)
            ->visit('/groups/'.$group->slug)
            ->click('[data-testid="request-join-button"]')
            ->waitFor('[data-testid="request-pending"]')
            ->assertPresent('[data-testid="request-pending"]');
    });
});
