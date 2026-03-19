<?php

use App\Models\Discussion;
use App\Models\DiscussionReply;
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
    $reply = DiscussionReply::factory()->create();

    expect($reply)->toBeInstanceOf(DiscussionReply::class)
        ->and($reply->exists)->toBeTrue()
        ->and($reply->body)->toBeString();
});

it('has discussion belongsTo relationship', function (): void {
    $reply = DiscussionReply::factory()->create();

    expect($reply->discussion())->toBeInstanceOf(BelongsTo::class)
        ->and($reply->discussion)->toBeInstanceOf(Discussion::class);
});

it('has user belongsTo relationship', function (): void {
    $reply = DiscussionReply::factory()->create();

    expect($reply->user())->toBeInstanceOf(BelongsTo::class)
        ->and($reply->user)->toBeInstanceOf(User::class);
});

it('uses soft deletes', function (): void {
    $reply = DiscussionReply::factory()->create();

    expect(in_array(SoftDeletingScope::class, array_map(
        fn ($scope) => $scope::class,
        $reply->getGlobalScopes()
    )))->toBeTrue();

    $reply->delete();

    expect($reply->trashed())->toBeTrue()
        ->and(DiscussionReply::withTrashed()->find($reply->id))->not->toBeNull()
        ->and(DiscussionReply::find($reply->id))->toBeNull();
});
