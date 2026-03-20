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

test('user can browse groups and see list', function () {
    $organizer = User::factory()->create();
    $group = Group::factory()->create([
        'name' => 'Laravel Copenhagen',
        'organizer_id' => $organizer->id,
    ]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);

    $this->browse(function (Browser $browser) {
        $browser->visit('/groups')
            ->assertSee('Browse Groups')
            ->assertSee('Laravel Copenhagen');
    });
});

test('user can filter groups by topic', function () {
    $organizer = User::factory()->create();
    $group = Group::factory()->create([
        'name' => 'Web Dev Group',
        'organizer_id' => $organizer->id,
    ]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);
    $group->attachTag('Web Development', 'interest');

    $this->browse(function (Browser $browser) {
        $browser->visit('/groups')
            ->assertSee('Web Dev Group')
            ->select('select[wire\\:model\\.live="topic"]', 'Web Development')
            ->waitForText('Web Dev Group')
            ->assertSee('Web Dev Group');
    });
});

test('user can click into a group from the list', function () {
    $organizer = User::factory()->create();
    $group = Group::factory()->create([
        'name' => 'Clickable Group',
        'organizer_id' => $organizer->id,
    ]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);

    $this->browse(function (Browser $browser) {
        $browser->visit('/groups')
            ->assertSee('Clickable Group')
            ->clickLink('Clickable Group')
            ->waitForText('Clickable Group')
            ->assertPathIs('/groups/'.$group->slug);
    });
});
