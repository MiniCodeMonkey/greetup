<?php

use App\Models\Block;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

it('creates a new conversation between two users', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $response = $this->actingAs($sender)
        ->post(route('messages.store'), [
            'recipient_id' => $recipient->id,
        ]);

    $conversation = Conversation::first();
    expect($conversation)->not->toBeNull();

    $participants = ConversationParticipant::where('conversation_id', $conversation->id)->get();
    expect($participants)->toHaveCount(2);
    expect($participants->pluck('user_id')->sort()->values()->all())
        ->toBe(collect([$sender->id, $recipient->id])->sort()->values()->all());

    $response->assertRedirect(route('messages.show', $conversation));
});

it('reopens an existing conversation instead of creating a new one', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $existingConversation = Conversation::factory()->create();
    ConversationParticipant::factory()->create([
        'conversation_id' => $existingConversation->id,
        'user_id' => $sender->id,
    ]);
    ConversationParticipant::factory()->create([
        'conversation_id' => $existingConversation->id,
        'user_id' => $recipient->id,
    ]);

    $response = $this->actingAs($sender)
        ->post(route('messages.store'), [
            'recipient_id' => $recipient->id,
        ]);

    expect(Conversation::count())->toBe(1);
    $response->assertRedirect(route('messages.show', $existingConversation));
});

it('rejects starting a conversation with a blocked user', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    Block::factory()->create([
        'blocker_id' => $sender->id,
        'blocked_id' => $recipient->id,
    ]);

    $response = $this->actingAs($sender)
        ->post(route('messages.store'), [
            'recipient_id' => $recipient->id,
        ]);

    expect(Conversation::count())->toBe(0);
    $response->assertSessionHasErrors('recipient_id');
});

it('rejects starting a conversation when blocked by recipient', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    Block::factory()->create([
        'blocker_id' => $recipient->id,
        'blocked_id' => $sender->id,
    ]);

    $response = $this->actingAs($sender)
        ->post(route('messages.store'), [
            'recipient_id' => $recipient->id,
        ]);

    expect(Conversation::count())->toBe(0);
    $response->assertSessionHasErrors('recipient_id');
});

it('enforces rate limit of 20 messages per minute', function (): void {
    $sender = User::factory()->create();

    for ($i = 0; $i < 20; $i++) {
        $recipient = User::factory()->create();
        $response = $this->actingAs($sender)
            ->post(route('messages.store'), [
                'recipient_id' => $recipient->id,
            ]);
        $response->assertRedirect();
    }

    $extraRecipient = User::factory()->create();
    $response = $this->actingAs($sender)
        ->post(route('messages.store'), [
            'recipient_id' => $extraRecipient->id,
        ]);

    $response->assertStatus(429);
});

it('requires authentication to start a conversation', function (): void {
    $recipient = User::factory()->create();

    $response = $this->post(route('messages.store'), [
        'recipient_id' => $recipient->id,
    ]);

    $response->assertRedirect(route('login'));
});

it('validates that recipient exists', function (): void {
    $sender = User::factory()->create();

    $response = $this->actingAs($sender)
        ->post(route('messages.store'), [
            'recipient_id' => 99999,
        ]);

    $response->assertSessionHasErrors('recipient_id');
});

it('prevents starting a conversation with yourself', function (): void {
    $sender = User::factory()->create();

    $response = $this->actingAs($sender)
        ->post(route('messages.store'), [
            'recipient_id' => $sender->id,
        ]);

    $response->assertSessionHasErrors('recipient_id');
    expect(Conversation::count())->toBe(0);
});
