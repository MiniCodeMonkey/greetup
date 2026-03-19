<?php

use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('can be created using the factory', function (): void {
    $message = DirectMessage::factory()->create();

    expect($message)->toBeInstanceOf(DirectMessage::class)
        ->and($message->exists)->toBeTrue()
        ->and($message->body)->toBeString();
});

it('has conversation belongsTo relationship', function (): void {
    $message = DirectMessage::factory()->create();

    expect($message->conversation())->toBeInstanceOf(BelongsTo::class)
        ->and($message->conversation)->toBeInstanceOf(Conversation::class);
});

it('has user belongsTo relationship', function (): void {
    $message = DirectMessage::factory()->create();

    expect($message->user())->toBeInstanceOf(BelongsTo::class)
        ->and($message->user)->toBeInstanceOf(User::class);
});

it('uses soft deletes', function (): void {
    $message = DirectMessage::factory()->create();

    expect(in_array(SoftDeletingScope::class, array_map(
        fn ($scope) => $scope::class,
        $message->getGlobalScopes()
    )))->toBeTrue();

    $message->delete();

    expect($message->trashed())->toBeTrue()
        ->and(DirectMessage::withTrashed()->find($message->id))->not->toBeNull()
        ->and(DirectMessage::find($message->id))->toBeNull();
});
