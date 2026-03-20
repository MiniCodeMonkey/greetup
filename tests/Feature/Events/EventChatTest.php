<?php

use App\Enums\GroupRole;
use App\Events\EventChatMessageSent;
use App\Livewire\EventChat;
use App\Models\Event;
use App\Models\EventChatMessage;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

function createChatEventWithMember(GroupRole $role = GroupRole::Member, bool $chatEnabled = true): array
{
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
        'is_chat_enabled' => $chatEnabled,
    ]);
    $event->hosts()->attach($organizer->id);

    $member = User::factory()->create();
    $group->members()->attach($member->id, ['role' => $role->value, 'joined_at' => now()]);

    return [$member, $event, $group, $organizer];
}

// --- Send as RSVP'd member ---

it('allows RSVP Going member to send a chat message', function (): void {
    EventFacade::fake([EventChatMessageSent::class]);

    [$member, $event] = createChatEventWithMember();
    Rsvp::factory()->for($event)->for($member)->going()->create();

    Livewire::actingAs($member)
        ->test(EventChat::class, ['event' => $event])
        ->set('body', 'Hello everyone!')
        ->call('sendMessage')
        ->assertSet('body', '');

    $message = EventChatMessage::where('event_id', $event->id)->where('user_id', $member->id)->first();
    expect($message)->not->toBeNull()
        ->and($message->body)->toBe('Hello everyone!');

    EventFacade::assertDispatched(EventChatMessageSent::class, function (EventChatMessageSent $e) use ($message): bool {
        return $e->message->id === $message->id;
    });
});

// --- Send as non-RSVP group member who joined ---

it('allows non-RSVP group member to send a chat message', function (): void {
    EventFacade::fake([EventChatMessageSent::class]);

    [$member, $event] = createChatEventWithMember();
    // No RSVP — member is a group member only

    Livewire::actingAs($member)
        ->test(EventChat::class, ['event' => $event])
        ->set('body', 'I might join later!')
        ->call('sendMessage')
        ->assertSet('body', '');

    expect(EventChatMessage::where('event_id', $event->id)->where('user_id', $member->id)->count())->toBe(1);
});

// --- Chat disabled returns 403 ---

it('returns 403 when chat is disabled', function (): void {
    [$member, $event] = createChatEventWithMember(chatEnabled: false);
    Rsvp::factory()->for($event)->for($member)->going()->create();

    Livewire::actingAs($member)
        ->test(EventChat::class, ['event' => $event])
        ->set('body', 'Hello!')
        ->call('sendMessage')
        ->assertForbidden();
});

// --- Non-group-member returns 403 ---

it('returns 403 when non-group-member sends a message', function (): void {
    [$member, $event, $group] = createChatEventWithMember();
    $nonMember = User::factory()->create();

    Livewire::actingAs($nonMember)
        ->test(EventChat::class, ['event' => $event])
        ->set('body', 'Trying to chat')
        ->call('sendMessage')
        ->assertForbidden();
});

// --- Reply (assert reply_to_id set) ---

it('allows replying to a message and sets reply_to_id', function (): void {
    EventFacade::fake([EventChatMessageSent::class]);

    [$member, $event] = createChatEventWithMember();
    Rsvp::factory()->for($event)->for($member)->going()->create();

    $parentMessage = EventChatMessage::factory()->for($event)->for($member, 'user')->create();

    Livewire::actingAs($member)
        ->test(EventChat::class, ['event' => $event])
        ->call('startReply', $parentMessage->id)
        ->assertSet('replyingTo', $parentMessage->id)
        ->set('body', 'Great point!')
        ->call('sendMessage')
        ->assertSet('replyingTo', null);

    $reply = EventChatMessage::where('event_id', $event->id)
        ->where('user_id', $member->id)
        ->where('reply_to_id', $parentMessage->id)
        ->first();

    expect($reply)->not->toBeNull()
        ->and($reply->body)->toBe('Great point!')
        ->and($reply->reply_to_id)->toBe($parentMessage->id);
});

// --- Edit own message ---

it('allows user to edit their own message', function (): void {
    [$member, $event] = createChatEventWithMember();
    Rsvp::factory()->for($event)->for($member)->going()->create();

    $message = EventChatMessage::factory()->for($event)->for($member, 'user')->create(['body' => 'Original']);

    Livewire::actingAs($member)
        ->test(EventChat::class, ['event' => $event])
        ->call('startEdit', $message->id)
        ->assertSet('editingId', $message->id)
        ->assertSet('editBody', 'Original')
        ->set('editBody', 'Updated message')
        ->call('saveEdit')
        ->assertSet('editingId', null);

    expect($message->fresh()->body)->toBe('Updated message');
});

// --- Delete own message (soft delete) ---

it('allows user to soft delete their own message', function (): void {
    [$member, $event] = createChatEventWithMember();
    Rsvp::factory()->for($event)->for($member)->going()->create();

    $message = EventChatMessage::factory()->for($event)->for($member, 'user')->create();

    Livewire::actingAs($member)
        ->test(EventChat::class, ['event' => $event])
        ->call('deleteMessage', $message->id)
        ->assertOk();

    expect($message->fresh()->trashed())->toBeTrue();
    expect(EventChatMessage::withTrashed()->find($message->id))->not->toBeNull();
});

// --- Leadership delete any message ---

it('allows event organizer to delete any message', function (): void {
    [$member, $event, $group] = createChatEventWithMember();
    Rsvp::factory()->for($event)->for($member)->going()->create();

    $leader = User::factory()->create();
    $group->members()->attach($leader->id, ['role' => GroupRole::EventOrganizer->value, 'joined_at' => now()]);

    $message = EventChatMessage::factory()->for($event)->for($member, 'user')->create();

    Livewire::actingAs($leader)
        ->test(EventChat::class, ['event' => $event])
        ->call('deleteMessage', $message->id)
        ->assertOk();

    expect($message->fresh()->trashed())->toBeTrue();
});

// --- Non-owner cannot edit another user's message (403) ---

it('returns 403 when non-owner tries to edit a message', function (): void {
    [$member, $event, $group] = createChatEventWithMember();
    Rsvp::factory()->for($event)->for($member)->going()->create();

    $otherMember = User::factory()->create();
    $group->members()->attach($otherMember->id, ['role' => GroupRole::Member->value, 'joined_at' => now()]);

    $message = EventChatMessage::factory()->for($event)->for($member, 'user')->create();

    Livewire::actingAs($otherMember)
        ->test(EventChat::class, ['event' => $event])
        ->call('startEdit', $message->id)
        ->assertForbidden();
});

// --- Non-owner cannot delete another user's message (403) ---

it('returns 403 when non-owner member tries to delete a message', function (): void {
    [$member, $event, $group] = createChatEventWithMember();
    Rsvp::factory()->for($event)->for($member)->going()->create();

    $otherMember = User::factory()->create();
    $group->members()->attach($otherMember->id, ['role' => GroupRole::Member->value, 'joined_at' => now()]);

    $message = EventChatMessage::factory()->for($event)->for($member, 'user')->create();

    Livewire::actingAs($otherMember)
        ->test(EventChat::class, ['event' => $event])
        ->call('deleteMessage', $message->id)
        ->assertForbidden();
});

// --- Rate limiting (11th message returns 429) ---

it('rate limits to 10 messages per 15 seconds and returns 429 on 11th', function (): void {
    EventFacade::fake([EventChatMessageSent::class]);

    [$member, $event] = createChatEventWithMember();
    Rsvp::factory()->for($event)->for($member)->going()->create();

    $component = Livewire::actingAs($member)
        ->test(EventChat::class, ['event' => $event]);

    for ($i = 1; $i <= 10; $i++) {
        $component
            ->set('body', "Message {$i}")
            ->call('sendMessage')
            ->assertOk();
    }

    expect(EventChatMessage::where('event_id', $event->id)->where('user_id', $member->id)->count())->toBe(10);

    $component
        ->set('body', 'Message 11')
        ->call('sendMessage')
        ->assertStatus(429);

    expect(EventChatMessage::where('event_id', $event->id)->where('user_id', $member->id)->count())->toBe(10);
});

// --- Broadcast event dispatched on send ---

it('dispatches EventChatMessageSent broadcast event on send', function (): void {
    EventFacade::fake([EventChatMessageSent::class]);

    [$member, $event] = createChatEventWithMember();
    Rsvp::factory()->for($event)->for($member)->going()->create();

    Livewire::actingAs($member)
        ->test(EventChat::class, ['event' => $event])
        ->set('body', 'Broadcast test')
        ->call('sendMessage');

    EventFacade::assertDispatched(EventChatMessageSent::class);
});
