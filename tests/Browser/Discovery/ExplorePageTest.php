<?php

use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;

uses(DatabaseMigrations::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('explore page shows search and filter controls', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/explore')
            ->assertSee('Events near')
            ->assertPresent('input[wire\\:model\\.live\\.debounce\\.300ms="search"]');
    });
});

test('user can search events on explore page', function () {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);
    Event::factory()->published()->create([
        'name' => 'Searchable Meetup',
        'group_id' => $group->id,
        'created_by' => $organizer->id,
    ]);
    Event::factory()->published()->create([
        'name' => 'Hidden Workshop',
        'group_id' => $group->id,
        'created_by' => $organizer->id,
    ]);

    $this->browse(function (Browser $browser) {
        $browser->visit('/explore')
            ->type('input[wire\\:model\\.live\\.debounce\\.300ms="search"]', 'Searchable')
            ->waitForText('Searchable Meetup')
            ->assertSee('Searchable Meetup');
    });
});
