<?php

use App\Models\Event;
use App\Models\EventChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('can be created using the factory', function (): void {
    $message = EventChatMessage::factory()->create();

    expect($message)->toBeInstanceOf(EventChatMessage::class)
        ->and($message->exists)->toBeTrue()
        ->and($message->body)->toBeString()
        ->and($message->reply_to_id)->toBeNull();
});

it('has event belongsTo relationship', function (): void {
    $message = EventChatMessage::factory()->create();

    expect($message->event())->toBeInstanceOf(BelongsTo::class)
        ->and($message->event)->toBeInstanceOf(Event::class);
});

it('has user belongsTo relationship', function (): void {
    $message = EventChatMessage::factory()->create();

    expect($message->user())->toBeInstanceOf(BelongsTo::class)
        ->and($message->user)->toBeInstanceOf(User::class);
});

it('has replyTo belongsTo relationship', function (): void {
    $parent = EventChatMessage::factory()->create();
    $reply = EventChatMessage::factory()->replyTo($parent)->create();

    expect($reply->replyTo())->toBeInstanceOf(BelongsTo::class)
        ->and($reply->replyTo)->toBeInstanceOf(EventChatMessage::class)
        ->and($reply->replyTo->id)->toBe($parent->id);
});

it('has replies hasMany relationship', function (): void {
    $parent = EventChatMessage::factory()->create();
    EventChatMessage::factory()->replyTo($parent)->create();

    expect($parent->replies())->toBeInstanceOf(HasMany::class)
        ->and($parent->replies)->toHaveCount(1);
});

it('uses soft deletes', function (): void {
    $message = EventChatMessage::factory()->create();

    expect(in_array(SoftDeletingScope::class, array_map(
        fn ($scope) => $scope::class,
        $message->getGlobalScopes()
    )))->toBeTrue();

    $message->delete();

    expect($message->trashed())->toBeTrue()
        ->and(EventChatMessage::withTrashed()->find($message->id))->not->toBeNull()
        ->and(EventChatMessage::find($message->id))->toBeNull();
});

it('uses the event_chat_messages table', function (): void {
    $message = EventChatMessage::factory()->create();

    expect($message->getTable())->toBe('event_chat_messages');
});

it('has replyTo factory state', function (): void {
    $parent = EventChatMessage::factory()->create();
    $reply = EventChatMessage::factory()->replyTo($parent)->create();

    expect($reply->reply_to_id)->toBe($parent->id)
        ->and($reply->event_id)->toBe($parent->event_id);
});
