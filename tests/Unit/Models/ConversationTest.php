<?php

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('can be created using the factory', function (): void {
    $conversation = Conversation::factory()->create();

    expect($conversation)->toBeInstanceOf(Conversation::class)
        ->and($conversation->exists)->toBeTrue();
});

it('has participants hasMany relationship', function (): void {
    $conversation = Conversation::factory()->create();
    ConversationParticipant::factory()->for($conversation)->create();

    expect($conversation->participants())->toBeInstanceOf(HasMany::class)
        ->and($conversation->participants)->toHaveCount(1)
        ->and($conversation->participants->first())->toBeInstanceOf(ConversationParticipant::class);
});

it('has messages hasMany relationship', function (): void {
    $conversation = Conversation::factory()->create();
    DirectMessage::factory()->for($conversation)->create();

    expect($conversation->messages())->toBeInstanceOf(HasMany::class)
        ->and($conversation->messages)->toHaveCount(1)
        ->and($conversation->messages->first())->toBeInstanceOf(DirectMessage::class);
});

it('user has conversations through participants', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();
    ConversationParticipant::factory()->for($conversation)->for($user)->create();

    expect($user->conversations())->toBeInstanceOf(BelongsToMany::class)
        ->and($user->conversations)->toHaveCount(1)
        ->and($user->conversations->first())->toBeInstanceOf(Conversation::class);
});
