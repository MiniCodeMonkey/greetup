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

test('member can open chat tab and send a message', function () {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);
    $event = Event::factory()->published()->create([
        'name' => 'Chat Event',
        'group_id' => $group->id,
        'created_by' => $organizer->id,
        'is_chat_enabled' => true,
    ]);
    $event->hosts()->attach($organizer->id);

    $member = User::factory()->create(['name' => 'Chat Member']);
    $group->members()->attach($member->id, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $this->browse(function (Browser $browser) use ($member, $group, $event) {
        $browser->loginAs($member)
            ->visit('/groups/'.$group->slug.'/events/'.$event->slug.'?tab=chat')
            ->waitFor('[data-testid="event-chat"]')
            ->assertPresent('[data-testid="chat-form"]');

        // Send a message
        $browser->type('[data-testid="chat-form"] textarea', 'Hello from Dusk!')
            ->click('[data-testid="chat-form"] button[type="submit"]')
            ->waitFor('[data-testid="chat-message"]')
            ->assertSee('Hello from Dusk!')
            ->assertSee('Chat Member');
    });
});
