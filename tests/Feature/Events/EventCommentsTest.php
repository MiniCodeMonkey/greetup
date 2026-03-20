<?php

use App\Enums\GroupRole;
use App\Enums\RsvpStatus;
use App\Livewire\CommentThread;
use App\Models\Comment;
use App\Models\Event;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use App\Notifications\EventCommentLiked;
use App\Notifications\EventCommentReply;
use App\Notifications\NewEventComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

function createEventWithMember(GroupRole $role = GroupRole::Member, bool $commentsEnabled = true): array
{
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
        'is_comments_enabled' => $commentsEnabled,
    ]);
    $event->hosts()->attach($organizer->id);

    $member = User::factory()->create();
    $group->members()->attach($member->id, ['role' => $role->value, 'joined_at' => now()]);

    return [$member, $event, $group, $organizer];
}

it('allows a group member to create a comment', function (): void {
    Notification::fake();

    [$member, $event] = createEventWithMember();

    Livewire::actingAs($member)
        ->test(CommentThread::class, ['event' => $event])
        ->set('body', 'This is a great event!')
        ->call('addComment')
        ->assertSet('body', '');

    expect(Comment::where('event_id', $event->id)->where('user_id', $member->id)->count())->toBe(1);

    $comment = Comment::where('event_id', $event->id)->first();
    expect($comment->body)->toBe('This is a great event!');
    expect($comment->body_html)->toContain('This is a great event!');
});

it('renders markdown in comments', function (): void {
    Notification::fake();

    [$member, $event] = createEventWithMember();

    Livewire::actingAs($member)
        ->test(CommentThread::class, ['event' => $event])
        ->set('body', '**Bold text** and *italic*')
        ->call('addComment');

    $comment = Comment::where('event_id', $event->id)->first();
    expect($comment->body_html)->toContain('<strong>Bold text</strong>');
    expect($comment->body_html)->toContain('<em>italic</em>');
});

it('allows replying to a comment (one level of nesting)', function (): void {
    Notification::fake();

    [$member, $event] = createEventWithMember();

    $parent = Comment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
    ]);

    Livewire::actingAs($member)
        ->test(CommentThread::class, ['event' => $event])
        ->call('startReply', $parent->id)
        ->assertSet('replyingTo', $parent->id)
        ->set('replyBody', 'Great question!')
        ->call('addReply')
        ->assertSet('replyingTo', null)
        ->assertSet('replyBody', '');

    $reply = Comment::where('parent_id', $parent->id)->first();
    expect($reply)->not->toBeNull();
    expect($reply->body)->toBe('Great question!');
    expect($reply->event_id)->toBe($event->id);
});

it('allows liking and unliking a comment', function (): void {
    Notification::fake();

    [$member, $event] = createEventWithMember();

    $comment = Comment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
    ]);

    $component = Livewire::actingAs($member)
        ->test(CommentThread::class, ['event' => $event]);

    // Like
    $component->call('toggleLike', $comment->id);
    expect($comment->fresh()->likedBy()->where('user_id', $member->id)->exists())->toBeTrue();

    // Unlike
    $component->call('toggleLike', $comment->id);
    expect($comment->fresh()->likedBy()->where('user_id', $member->id)->exists())->toBeFalse();
});

it('allows author to delete own comment', function (): void {
    Notification::fake();

    [$member, $event] = createEventWithMember();

    $comment = Comment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $member->id,
    ]);

    Livewire::actingAs($member)
        ->test(CommentThread::class, ['event' => $event])
        ->call('deleteComment', $comment->id);

    expect($comment->fresh()->trashed())->toBeTrue();
});

it('allows leadership (co_organizer+) to delete any comment', function (): void {
    Notification::fake();

    [$coOrganizer, $event, $group] = createEventWithMember(GroupRole::CoOrganizer);

    $otherUser = User::factory()->create();
    $comment = Comment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $otherUser->id,
    ]);

    Livewire::actingAs($coOrganizer)
        ->test(CommentThread::class, ['event' => $event])
        ->call('deleteComment', $comment->id);

    expect($comment->fresh()->trashed())->toBeTrue();
});

it('returns 403 when comments are disabled', function (): void {
    [$member, $event] = createEventWithMember(commentsEnabled: false);

    Livewire::actingAs($member)
        ->test(CommentThread::class, ['event' => $event])
        ->call('addComment')
        ->assertForbidden();
});

it('dispatches NewEventComment notification to hosts and going members', function (): void {
    Notification::fake();

    [$member, $event, $group, $organizer] = createEventWithMember();

    // Add organizer as group member too
    $group->members()->attach($organizer->id, ['role' => GroupRole::Organizer->value, 'joined_at' => now()]);

    // Add a going RSVP user
    $goingUser = User::factory()->create();
    $group->members()->attach($goingUser->id, ['role' => GroupRole::Member->value, 'joined_at' => now()]);
    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $goingUser->id,
        'status' => RsvpStatus::Going,
        'guest_count' => 0,
    ]);

    Livewire::actingAs($member)
        ->test(CommentThread::class, ['event' => $event])
        ->set('body', 'Hello everyone!')
        ->call('addComment');

    // Host (organizer) should be notified
    Notification::assertSentTo($organizer, NewEventComment::class);

    // Going member should be notified
    Notification::assertSentTo($goingUser, NewEventComment::class);

    // Comment author should NOT be notified
    Notification::assertNotSentTo($member, NewEventComment::class);
});

it('dispatches EventCommentReply notification to parent comment author', function (): void {
    Notification::fake();

    [$member, $event] = createEventWithMember();

    $parentAuthor = User::factory()->create();
    $parent = Comment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $parentAuthor->id,
    ]);

    Livewire::actingAs($member)
        ->test(CommentThread::class, ['event' => $event])
        ->call('startReply', $parent->id)
        ->set('replyBody', 'Thanks for your comment!')
        ->call('addReply');

    Notification::assertSentTo($parentAuthor, EventCommentReply::class);
});

it('dispatches EventCommentLiked notification to comment author', function (): void {
    Notification::fake();

    [$member, $event] = createEventWithMember();

    $commentAuthor = User::factory()->create();
    $comment = Comment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $commentAuthor->id,
    ]);

    Livewire::actingAs($member)
        ->test(CommentThread::class, ['event' => $event])
        ->call('toggleLike', $comment->id);

    Notification::assertSentTo($commentAuthor, EventCommentLiked::class);
});

it('does not send EventCommentLiked notification when liking own comment', function (): void {
    Notification::fake();

    [$member, $event] = createEventWithMember();

    $comment = Comment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $member->id,
    ]);

    Livewire::actingAs($member)
        ->test(CommentThread::class, ['event' => $event])
        ->call('toggleLike', $comment->id);

    Notification::assertNotSentTo($member, EventCommentLiked::class);
});

it('paginates comments at 15 per page', function (): void {
    Notification::fake();

    [$member, $event] = createEventWithMember();

    Comment::factory()->count(20)->create([
        'event_id' => $event->id,
    ]);

    $component = Livewire::actingAs($member)
        ->test(CommentThread::class, ['event' => $event]);

    $component->assertViewHas('comments', function ($comments) {
        return $comments->perPage() === 15 && $comments->total() === 20;
    });
});

it('does not allow non-member to create a comment', function (): void {
    $nonMember = User::factory()->create();
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'is_comments_enabled' => true,
    ]);

    Livewire::actingAs($nonMember)
        ->test(CommentThread::class, ['event' => $event])
        ->set('body', 'I should not be able to comment')
        ->call('addComment')
        ->assertForbidden();
});

it('does not allow a regular member to delete another users comment', function (): void {
    [$member, $event] = createEventWithMember();

    $otherUser = User::factory()->create();
    $comment = Comment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $otherUser->id,
    ]);

    Livewire::actingAs($member)
        ->test(CommentThread::class, ['event' => $event])
        ->call('deleteComment', $comment->id)
        ->assertForbidden();

    expect($comment->fresh()->trashed())->toBeFalse();
});
