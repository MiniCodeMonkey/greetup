<?php

use App\Models\Comment;
use App\Models\Event;
use App\Models\EventCommentLike;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('can be created using the factory', function (): void {
    $comment = Comment::factory()->create();

    expect($comment)->toBeInstanceOf(Comment::class)
        ->and($comment->exists)->toBeTrue()
        ->and($comment->body)->toBeString()
        ->and($comment->body_html)->toBeString()
        ->and($comment->parent_id)->toBeNull();
});

it('has event belongsTo relationship', function (): void {
    $comment = Comment::factory()->create();

    expect($comment->event())->toBeInstanceOf(BelongsTo::class)
        ->and($comment->event)->toBeInstanceOf(Event::class);
});

it('has user belongsTo relationship', function (): void {
    $comment = Comment::factory()->create();

    expect($comment->user())->toBeInstanceOf(BelongsTo::class)
        ->and($comment->user)->toBeInstanceOf(User::class);
});

it('has parent belongsTo relationship', function (): void {
    $parent = Comment::factory()->create();
    $reply = Comment::factory()->reply($parent)->create();

    expect($reply->parent())->toBeInstanceOf(BelongsTo::class)
        ->and($reply->parent)->toBeInstanceOf(Comment::class)
        ->and($reply->parent->id)->toBe($parent->id);
});

it('has replies hasMany relationship', function (): void {
    $parent = Comment::factory()->create();
    Comment::factory()->reply($parent)->create();

    expect($parent->replies())->toBeInstanceOf(HasMany::class)
        ->and($parent->replies)->toHaveCount(1);
});

it('has likedBy belongsToMany relationship', function (): void {
    $comment = Comment::factory()->create();
    $user = User::factory()->create();

    EventCommentLike::factory()->create([
        'comment_id' => $comment->id,
        'user_id' => $user->id,
    ]);

    expect($comment->likedBy())->toBeInstanceOf(BelongsToMany::class)
        ->and($comment->likedBy)->toHaveCount(1)
        ->and($comment->likedBy->first()->id)->toBe($user->id);
});

it('uses soft deletes', function (): void {
    $comment = Comment::factory()->create();

    expect(in_array(SoftDeletingScope::class, array_map(
        fn ($scope) => $scope::class,
        $comment->getGlobalScopes()
    )))->toBeTrue();

    $comment->delete();

    expect($comment->trashed())->toBeTrue()
        ->and(Comment::withTrashed()->find($comment->id))->not->toBeNull()
        ->and(Comment::find($comment->id))->toBeNull();
});

it('uses the event_comments table', function (): void {
    $comment = Comment::factory()->create();

    expect($comment->getTable())->toBe('event_comments');
});

it('has reply factory state', function (): void {
    $parent = Comment::factory()->create();
    $reply = Comment::factory()->reply($parent)->create();

    expect($reply->parent_id)->toBe($parent->id)
        ->and($reply->event_id)->toBe($parent->event_id);
});
