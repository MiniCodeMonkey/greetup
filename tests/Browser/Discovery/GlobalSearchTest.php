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

test('global search page loads and accepts queries', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/search')
            ->assertSee('Search')
            ->assertPresent('input[wire\\:model\\.live\\.debounce\\.300ms="query"]');
    });
});

test('global search returns results across models', function () {
    $organizer = User::factory()->create(['name' => 'Searchable User']);
    $group = Group::factory()->create([
        'name' => 'Searchable Group',
        'organizer_id' => $organizer->id,
    ]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);

    $this->browse(function (Browser $browser) {
        $browser->visit('/search')
            ->type('input[wire\\:model\\.live\\.debounce\\.300ms="query"]', 'Searchable')
            ->waitForText('Groups')
            ->assertSee('Searchable Group');
    });
});
