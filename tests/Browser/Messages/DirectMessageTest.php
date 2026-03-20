<?php

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;

uses(DatabaseMigrations::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('user can visit profile and start a conversation via Message button', function () {
    $sender = User::factory()->create(['name' => 'Sender User']);
    $recipient = User::factory()->create(['name' => 'Recipient User']);

    $this->browse(function (Browser $browser) use ($sender, $recipient) {
        $browser->loginAs($sender)
            ->visit('/members/'.$recipient->id)
            ->assertSee('Recipient User')
            ->press('Message')
            ->waitForText('Recipient User')
            ->assertPathBeginsWith('/messages/');
    });
});

test('user can send a message and see it in conversation list', function () {
    $sender = User::factory()->create(['name' => 'Msg Sender']);
    $recipient = User::factory()->create(['name' => 'Msg Recipient']);

    // Create conversation manually
    $conversation = Conversation::create();
    ConversationParticipant::create([
        'conversation_id' => $conversation->id,
        'user_id' => $sender->id,
    ]);
    ConversationParticipant::create([
        'conversation_id' => $conversation->id,
        'user_id' => $recipient->id,
    ]);

    $this->browse(function (Browser $browser) use ($sender, $conversation) {
        $browser->loginAs($sender)
            ->visit('/messages/'.$conversation->id)
            ->waitFor('[data-testid="dm-form"]')
            ->type('[data-testid="dm-form"] textarea', 'Hello from Dusk test!')
            ->click('[data-testid="dm-form"] button[type="submit"]')
            ->waitFor('[data-testid="dm-message"]')
            ->assertSee('Hello from Dusk test!');

        // Verify it appears in conversation list
        $browser->visit('/messages')
            ->assertSee('Hello from Dusk test!');
    });
});
