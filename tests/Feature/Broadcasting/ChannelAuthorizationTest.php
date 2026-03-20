<?php

use App\Enums\GroupRole;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Event;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Pusher\Pusher;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    config(['broadcasting.default' => 'reverb']);

    // Mock the Pusher SDK so auth signatures can be generated without a real Reverb server
    $pusherMock = Mockery::mock(Pusher::class);
    $pusherMock->shouldReceive('authorizeChannel')
        ->andReturn(json_encode(['auth' => 'test:signature']));

    app()->singleton('broadcaster.reverb', fn () => new PusherBroadcaster($pusherMock));

    // Replace the default broadcaster driver with our mocked one
    Broadcast::swap(app('broadcaster.reverb'));

    // Re-register channel definitions on the mocked broadcaster
    require base_path('routes/channels.php');
});

// --- user.{userId}.notifications channel ---

it('authorizes user for their own notifications channel', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '12345.67890',
            'channel_name' => "private-user.{$user->id}.notifications",
        ])
        ->assertOk();
});

it('rejects user from another user notifications channel', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '12345.67890',
            'channel_name' => "private-user.{$other->id}.notifications",
        ])
        ->assertForbidden();
});

it('rejects unauthenticated user from notifications channel', function (): void {
    $user = User::factory()->create();

    $this->postJson('/broadcasting/auth', [
        'socket_id' => '12345.67890',
        'channel_name' => "private-user.{$user->id}.notifications",
    ])->assertForbidden();
});

// --- conversation.{conversationId} channel ---

it('authorizes participant for conversation channel', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();
    ConversationParticipant::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '12345.67890',
            'channel_name' => "private-conversation.{$conversation->id}",
        ])
        ->assertOk();
});

it('rejects non-participant from conversation channel', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '12345.67890',
            'channel_name' => "private-conversation.{$conversation->id}",
        ])
        ->assertForbidden();
});

// --- event.{eventId}.chat channel ---

it('authorizes RSVP Going member for event chat channel', function (): void {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
        'is_chat_enabled' => true,
    ]);

    $member = User::factory()->create();
    Rsvp::factory()->for($event)->for($member)->going()->create();

    $this->actingAs($member)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '12345.67890',
            'channel_name' => "private-event.{$event->id}.chat",
        ])
        ->assertOk();
});

it('authorizes group member for event chat channel', function (): void {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
        'is_chat_enabled' => true,
    ]);

    $member = User::factory()->create();
    $group->members()->attach($member->id, ['role' => GroupRole::Member->value, 'joined_at' => now()]);

    $this->actingAs($member)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '12345.67890',
            'channel_name' => "private-event.{$event->id}.chat",
        ])
        ->assertOk();
});

it('rejects non-member non-RSVP user from event chat channel', function (): void {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
        'is_chat_enabled' => true,
    ]);

    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '12345.67890',
            'channel_name' => "private-event.{$event->id}.chat",
        ])
        ->assertForbidden();
});

it('rejects user from event chat channel when chat is disabled', function (): void {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
        'is_chat_enabled' => false,
    ]);

    $member = User::factory()->create();
    $group->members()->attach($member->id, ['role' => GroupRole::Member->value, 'joined_at' => now()]);

    $this->actingAs($member)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '12345.67890',
            'channel_name' => "private-event.{$event->id}.chat",
        ])
        ->assertForbidden();
});
