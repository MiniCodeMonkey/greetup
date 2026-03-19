<?php

use App\Models\Comment;
use App\Models\EventCommentLike;
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
    $like = EventCommentLike::factory()->create();

    expect($like)->toBeInstanceOf(EventCommentLike::class)
        ->and($like->exists)->toBeTrue()
        ->and($like->created_at)->not->toBeNull();
});

it('has comment belongsTo relationship', function (): void {
    $like = EventCommentLike::factory()->create();

    expect($like->comment())->toBeInstanceOf(BelongsTo::class)
        ->and($like->comment)->toBeInstanceOf(Comment::class);
});

it('has user belongsTo relationship', function (): void {
    $like = EventCommentLike::factory()->create();

    expect($like->user())->toBeInstanceOf(BelongsTo::class)
        ->and($like->user)->toBeInstanceOf(User::class);
});

it('enforces unique constraint on comment_id and user_id', function (): void {
    $comment = Comment::factory()->create();
    $user = User::factory()->create();

    EventCommentLike::factory()->create(['comment_id' => $comment->id, 'user_id' => $user->id]);

    EventCommentLike::factory()->create(['comment_id' => $comment->id, 'user_id' => $user->id]);
})->throws(QueryException::class);

it('casts created_at to datetime', function (): void {
    $like = EventCommentLike::factory()->create();

    expect($like->created_at)->toBeInstanceOf(Carbon\Carbon::class);
});
