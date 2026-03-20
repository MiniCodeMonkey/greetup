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

test('user can browse events on explore page', function () {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);
    $event = Event::factory()->published()->create([
        'name' => 'Laravel Meetup',
        'group_id' => $group->id,
        'created_by' => $organizer->id,
    ]);
    $event->hosts()->attach($organizer->id);

    $this->browse(function (Browser $browser) {
        $browser->visit('/explore')
            ->assertSee('Events near')
            ->assertSee('Laravel Meetup');
    });
});

test('user can click into an event from explore', function () {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);
    $event = Event::factory()->published()->create([
        'name' => 'Click Event',
        'group_id' => $group->id,
        'created_by' => $organizer->id,
    ]);
    $event->hosts()->attach($organizer->id);

    $this->browse(function (Browser $browser) use ($group, $event) {
        $browser->visit('/explore')
            ->clickLink('Click Event')
            ->waitForText('Click Event')
            ->assertPathIs('/groups/'.$group->slug.'/events/'.$event->slug);
    });
});
