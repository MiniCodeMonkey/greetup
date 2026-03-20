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

test('user can RSVP Going, see confirmation, then change to Not Going', function () {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);
    $event = Event::factory()->published()->create([
        'name' => 'RSVP Event',
        'group_id' => $group->id,
        'created_by' => $organizer->id,
    ]);
    $event->hosts()->attach($organizer->id);

    $member = User::factory()->create(['name' => 'RSVP Member']);
    $group->members()->attach($member->id, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $this->browse(function (Browser $browser) use ($member, $group, $event) {
        $browser->loginAs($member)
            ->visit('/groups/'.$group->slug.'/events/'.$event->slug)
            ->assertSee('RSVP Event')
            ->waitFor('[data-testid="rsvp-going"]')
            ->click('[data-testid="rsvp-going"]')
            ->waitFor('[data-testid="rsvp-status-going"]')
            ->assertSeeIn('[data-testid="rsvp-status-going"]', 'Going');

        // Change to Not Going
        $browser->click('[data-testid="rsvp-not-going"]')
            ->waitFor('[data-testid="rsvp-going"]')
            ->assertPresent('[data-testid="rsvp-going"]');

        // Re-RSVP
        $browser->click('[data-testid="rsvp-going"]')
            ->waitFor('[data-testid="rsvp-status-going"]')
            ->assertSeeIn('[data-testid="rsvp-status-going"]', 'Going');
    });
});

test('user appears in attendee list after RSVP', function () {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);
    $event = Event::factory()->published()->create([
        'name' => 'Attendee Event',
        'group_id' => $group->id,
        'created_by' => $organizer->id,
    ]);
    $event->hosts()->attach($organizer->id);

    $member = User::factory()->create(['name' => 'Attendee Person']);
    $group->members()->attach($member->id, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $this->browse(function (Browser $browser) use ($member, $group, $event) {
        $browser->loginAs($member)
            ->visit('/groups/'.$group->slug.'/events/'.$event->slug)
            ->waitFor('[data-testid="rsvp-going"]')
            ->click('[data-testid="rsvp-going"]')
            ->waitFor('[data-testid="rsvp-status-going"]');

        // Check attendees tab
        $browser->clickLink('Attendees')
            ->waitFor('[data-testid="attendees-tab"]')
            ->assertSee('1 person going');
    });
});
