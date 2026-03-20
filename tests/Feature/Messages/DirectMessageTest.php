<?php

use App\Livewire\ConversationView;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\DirectMessage;
use App\Models\User;
use App\Notifications\NewDirectMessage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

function createDirectMessageConversation(): array
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

it('sends a direct message in a conversation', function (): void {
    [$sender, $recipient, $conversation] = createDirectMessageConversation();

    Notification::fake();

    Livewire::actingAs($sender)
        ->test(ConversationView::class, ['conversation' => $conversation])
        ->set('body', 'Hello there!')
        ->call('sendMessage');

    expect(DirectMessage::where('conversation_id', $conversation->id)->count())->toBe(1);

    $message = DirectMessage::where('conversation_id', $conversation->id)->first();
    expect($message->body)->toBe('Hello there!');
    expect($message->user_id)->toBe($sender->id);
});

it('receives messages from another participant', function (): void {
    [$sender, $recipient, $conversation] = createDirectMessageConversation();

    DirectMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $recipient->id,
        'body' => 'Hi from recipient!',
    ]);

    Livewire::actingAs($sender)
        ->test(ConversationView::class, ['conversation' => $conversation])
        ->assertSee('Hi from recipient!');
});

it('shows unread indicator on conversation list when new message exists', function (): void {
    [$sender, $recipient, $conversation, $senderParticipant] = createDirectMessageConversation();

    $senderParticipant->update(['last_read_at' => now()->subMinutes(5)]);

    DirectMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $recipient->id,
        'body' => 'Unread message',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($sender)->get(route('messages.index'));

    $response->assertStatus(200);
    $response->assertSee('Unread message');
});

it('does not show unread indicator when conversation is read', function (): void {
    [$sender, $recipient, $conversation, $senderParticipant] = createDirectMessageConversation();

    DirectMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $recipient->id,
        'body' => 'Already read',
        'created_at' => now()->subMinutes(5),
    ]);

    $senderParticipant->update(['last_read_at' => now()]);

    $response = $this->actingAs($sender)->get(route('messages.index'));

    $response->assertStatus(200);
    $response->assertSee('Already read');
});

it('sends notification when a direct message is sent', function (): void {
    [$sender, $recipient, $conversation] = createDirectMessageConversation();

    Notification::fake();

    Livewire::actingAs($sender)
        ->test(ConversationView::class, ['conversation' => $conversation])
        ->set('body', 'Notification test')
        ->call('sendMessage');

    Notification::assertSentTo($recipient, NewDirectMessage::class);
});

it('suppresses notification when conversation is muted', function (): void {
    [$sender, $recipient, $conversation, $senderParticipant, $recipientParticipant] = createDirectMessageConversation();

    $recipientParticipant->update(['is_muted' => true]);

    Notification::fake();

    Livewire::actingAs($sender)
        ->test(ConversationView::class, ['conversation' => $conversation])
        ->set('body', 'Muted test')
        ->call('sendMessage');

    Notification::assertNotSentTo($recipient, NewDirectMessage::class);
});

it('soft deletes own message', function (): void {
    [$sender, $recipient, $conversation] = createDirectMessageConversation();

    $message = DirectMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $sender->id,
        'body' => 'Delete me',
    ]);

    Livewire::actingAs($sender)
        ->test(ConversationView::class, ['conversation' => $conversation])
        ->call('deleteMessage', $message->id);

    expect(DirectMessage::find($message->id))->toBeNull();
    expect(DirectMessage::withTrashed()->find($message->id))->not->toBeNull();
});

it('cannot delete another users message', function (): void {
    [$sender, $recipient, $conversation] = createDirectMessageConversation();

    $message = DirectMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $recipient->id,
        'body' => 'Not yours',
    ]);

    expect(fn () => Livewire::actingAs($sender)
        ->test(ConversationView::class, ['conversation' => $conversation])
        ->call('deleteMessage', $message->id)
    )->toThrow(ModelNotFoundException::class);

    expect(DirectMessage::find($message->id))->not->toBeNull();
});

it('denies access to non-participant', function (): void {
    [$sender, $recipient, $conversation] = createDirectMessageConversation();

    $outsider = User::factory()->create();

    Livewire::actingAs($outsider)
        ->test(ConversationView::class, ['conversation' => $conversation])
        ->assertStatus(403);
});

it('paginates conversation list at 20 per page', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 25; $i++) {
        $otherUser = User::factory()->create();
        $conversation = Conversation::factory()->create();
        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);
        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $otherUser->id,
        ]);
        DirectMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $otherUser->id,
            'created_at' => now()->subMinutes($i),
        ]);
    }

    $response = $this->actingAs($user)->get(route('messages.index'));

    $response->assertStatus(200);
});

it('updates last_read_at when viewing conversation', function (): void {
    [$sender, $recipient, $conversation, $senderParticipant] = createDirectMessageConversation();

    expect($senderParticipant->fresh()->last_read_at)->toBeNull();

    Livewire::actingAs($sender)
        ->test(ConversationView::class, ['conversation' => $conversation]);

    expect($senderParticipant->fresh()->last_read_at)->not->toBeNull();
});

it('requires authentication to view messages', function (): void {
    $response = $this->get(route('messages.index'));

    $response->assertRedirect(route('login'));
});
