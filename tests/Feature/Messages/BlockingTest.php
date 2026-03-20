<?php

use App\Livewire\ConversationView;
use App\Models\Block;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

function createBlockingConversation(): array
{
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $senderParticipant = ConversationParticipant::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $sender->id,
    ]);

    $recipientParticipant = ConversationParticipant::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $recipient->id,
    ]);

    return [$sender, $recipient, $conversation, $senderParticipant, $recipientParticipant];
}

it('creates a block record when blocking a user', function (): void {
    $blocker = User::factory()->create();
    $blocked = User::factory()->create();

    $response = $this->actingAs($blocker)
        ->post(route('members.block', $blocked));

    $response->assertRedirect();
    $response->assertSessionHas('status', 'User blocked.');

    expect(Block::where('blocker_id', $blocker->id)->where('blocked_id', $blocked->id)->exists())
        ->toBeTrue();
});

it('prevents blocking yourself', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('members.block', $user));

    $response->assertSessionHasErrors('block');
    expect(Block::count())->toBe(0);
});

it('does not create duplicate block records', function (): void {
    $blocker = User::factory()->create();
    $blocked = User::factory()->create();

    Block::factory()->create([
        'blocker_id' => $blocker->id,
        'blocked_id' => $blocked->id,
    ]);

    $this->actingAs($blocker)
        ->post(route('members.block', $blocked));

    expect(Block::where('blocker_id', $blocker->id)->where('blocked_id', $blocked->id)->count())
        ->toBe(1);
});

it('rejects DM from blocked user via Livewire', function (): void {
    [$sender, $recipient, $conversation] = createBlockingConversation();

    Block::factory()->create([
        'blocker_id' => $recipient->id,
        'blocked_id' => $sender->id,
    ]);

    Livewire::actingAs($sender)
        ->test(ConversationView::class, ['conversation' => $conversation])
        ->set('body', 'Hello!')
        ->call('sendMessage')
        ->assertForbidden();

    expect(DirectMessage::count())->toBe(0);
});

it('rejects DM to user the sender blocked', function (): void {
    [$sender, $recipient, $conversation] = createBlockingConversation();

    Block::factory()->create([
        'blocker_id' => $sender->id,
        'blocked_id' => $recipient->id,
    ]);

    Livewire::actingAs($sender)
        ->test(ConversationView::class, ['conversation' => $conversation])
        ->set('body', 'Hello!')
        ->call('sendMessage')
        ->assertForbidden();

    expect(DirectMessage::count())->toBe(0);
});

it('hides blocker profile from blocked user with 403', function (): void {
    $blocker = User::factory()->create(['profile_visibility' => 'public']);
    $blocked = User::factory()->create();

    Block::factory()->create([
        'blocker_id' => $blocker->id,
        'blocked_id' => $blocked->id,
    ]);

    $response = $this->actingAs($blocked)
        ->get(route('members.show', $blocker));

    $response->assertForbidden();
});

it('hides conversation for both users when blocked', function (): void {
    [$sender, $recipient, $conversation] = createBlockingConversation();

    DirectMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $sender->id,
        'body' => 'Hey there',
    ]);

    // Before blocking, both see the conversation
    $response = $this->actingAs($sender)->get(route('messages.index'));
    $response->assertOk();
    expect($response->viewData('conversations'))->toHaveCount(1);

    $response = $this->actingAs($recipient)->get(route('messages.index'));
    $response->assertOk();
    expect($response->viewData('conversations'))->toHaveCount(1);

    // Block
    Block::factory()->create([
        'blocker_id' => $sender->id,
        'blocked_id' => $recipient->id,
    ]);

    // After blocking, neither sees the conversation
    $response = $this->actingAs($sender)->get(route('messages.index'));
    $response->assertOk();
    expect($response->viewData('conversations'))->toHaveCount(0);

    $response = $this->actingAs($recipient)->get(route('messages.index'));
    $response->assertOk();
    expect($response->viewData('conversations'))->toHaveCount(0);
});

it('unblocks a user', function (): void {
    $blocker = User::factory()->create();
    $blocked = User::factory()->create();

    Block::factory()->create([
        'blocker_id' => $blocker->id,
        'blocked_id' => $blocked->id,
    ]);

    $response = $this->actingAs($blocker)
        ->delete(route('members.unblock', $blocked));

    $response->assertRedirect();
    $response->assertSessionHas('status', 'User unblocked.');

    expect(Block::where('blocker_id', $blocker->id)->where('blocked_id', $blocked->id)->exists())
        ->toBeFalse();
});

it('conversation reappears after unblocking', function (): void {
    [$sender, $recipient, $conversation] = createBlockingConversation();

    DirectMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $sender->id,
        'body' => 'Before block',
    ]);

    // Block
    $block = Block::factory()->create([
        'blocker_id' => $sender->id,
        'blocked_id' => $recipient->id,
    ]);

    // Conversation hidden
    $response = $this->actingAs($sender)->get(route('messages.index'));
    expect($response->viewData('conversations'))->toHaveCount(0);

    // Unblock
    $this->actingAs($sender)->delete(route('members.unblock', $recipient));

    // Conversation reappears
    $response = $this->actingAs($sender)->get(route('messages.index'));
    expect($response->viewData('conversations'))->toHaveCount(1);

    $response = $this->actingAs($recipient)->get(route('messages.index'));
    expect($response->viewData('conversations'))->toHaveCount(1);
});

it('requires authentication to block or unblock', function (): void {
    $user = User::factory()->create();

    $this->post(route('members.block', $user))->assertRedirect(route('login'));
    $this->delete(route('members.unblock', $user))->assertRedirect(route('login'));
});
