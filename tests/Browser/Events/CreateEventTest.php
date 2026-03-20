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

test('organizer can create an event and see the event page', function () {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);

    $this->browse(function (Browser $browser) use ($organizer, $group) {
        $startsAt = now()->addDays(7)->format('Y-m-d\TH:i');

        $browser->loginAs($organizer)
            ->visit('/groups/'.$group->slug.'/events/create')
            ->type('name', 'Dusk Browser Event')
            ->type('description', 'Event created by Dusk test.')
            ->radio('event_type', 'in_person')
            ->value('#starts_at', $startsAt)
            ->type('venue_name', 'Test Venue')
            ->type('venue_address', '123 Test Street')
            ->press('Publish Event')
            ->waitForText('Dusk Browser Event')
            ->assertSee('Dusk Browser Event')
            ->assertSee('Event created by Dusk test.');
    });
});

test('organizer can edit an event and see updates', function () {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);

    $event = Event::factory()->published()->create([
        'name' => 'Original Event Name',
        'group_id' => $group->id,
        'created_by' => $organizer->id,
    ]);
    $event->hosts()->attach($organizer->id);

    $this->browse(function (Browser $browser) use ($organizer, $group, $event) {
        $browser->loginAs($organizer)
            ->visit('/groups/'.$group->slug.'/events/'.$event->slug.'/edit')
            ->clear('name')
            ->type('name', 'Updated Event Name')
            ->press('Save Changes')
            ->waitForText('Updated Event Name')
            ->assertSee('Updated Event Name');
    });
});
