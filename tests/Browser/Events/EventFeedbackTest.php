<?php

use App\Models\Event;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;

uses(DatabaseMigrations::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('attendee can leave feedback on a past event', function () {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);
    $event = Event::factory()->past()->create([
        'name' => 'Past Feedback Event',
        'group_id' => $group->id,
        'created_by' => $organizer->id,
    ]);
    $event->hosts()->attach($organizer->id);

    $member = User::factory()->create(['name' => 'Feedback Member']);
    $group->members()->attach($member->id, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    // Create an RSVP so user can leave feedback
    Rsvp::factory()->going()->create([
        'event_id' => $event->id,
        'user_id' => $member->id,
    ]);

    $this->browse(function (Browser $browser) use ($member, $group, $event) {
        $browser->loginAs($member)
            ->visit('/groups/'.$group->slug.'/events/'.$event->slug.'?tab=feedback')
            ->waitFor('[data-testid="feedback-form"]')
            ->assertSee('Leave your feedback');

        // Click 4th star for rating
        $browser->click('[data-testid="feedback-form"] button:nth-child(4)')
            ->type('#feedback-body', 'Great event, would attend again!')
            ->press('Submit Feedback')
            ->waitFor('[data-testid="user-feedback"]')
            ->assertSee('You already submitted feedback');
    });
});
