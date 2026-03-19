<?php

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('can be created using the factory', function (): void {
    $participant = ConversationParticipant::factory()->create();

    expect($participant)->toBeInstanceOf(ConversationParticipant::class)
        ->and($participant->exists)->toBeTrue()
        ->and($participant->last_read_at)->toBeNull()
        ->and($participant->is_muted)->toBeFalse();
});

it('has conversation belongsTo relationship', function (): void {
    $participant = ConversationParticipant::factory()->create();

    expect($participant->conversation())->toBeInstanceOf(BelongsTo::class)
        ->and($participant->conversation)->toBeInstanceOf(Conversation::class);
});

it('has user belongsTo relationship', function (): void {
    $participant = ConversationParticipant::factory()->create();

    expect($participant->user())->toBeInstanceOf(BelongsTo::class)
        ->and($participant->user)->toBeInstanceOf(User::class);
});

it('enforces unique conversation and user combination', function (): void {
    $participant = ConversationParticipant::factory()->create();

    ConversationParticipant::factory()->create([
        'conversation_id' => $participant->conversation_id,
        'user_id' => $participant->user_id,
    ]);
})->throws(QueryException::class);

it('has muted factory state', function (): void {
    $participant = ConversationParticipant::factory()->muted()->create();

    expect($participant->is_muted)->toBeTrue();
});
