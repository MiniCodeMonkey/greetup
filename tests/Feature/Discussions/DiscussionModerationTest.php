<?php

use App\Enums\GroupRole;
use App\Livewire\DiscussionThread;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

function createModerationSetup(GroupRole $role = GroupRole::Member): array
{
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, ['role' => GroupRole::Organizer->value, 'joined_at' => now()]);

    $user = User::factory()->create();
    $group->members()->attach($user->id, ['role' => $role->value, 'joined_at' => now()]);

    $discussion = Discussion::factory()->for($group)->for($organizer, 'user')->create();

    return [$user, $group, $organizer, $discussion];
}

// --- Pin / Unpin ---

it('allows a co-organizer to pin a discussion', function (): void {
    [$user, $group, $organizer, $discussion] = createModerationSetup(GroupRole::CoOrganizer);

    Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->call('togglePin')
        ->assertHasNoErrors();

    $discussion->refresh();
    expect($discussion->is_pinned)->toBeTrue();
});

it('allows a co-organizer to unpin a discussion', function (): void {
    [$user, $group, $organizer, $discussion] = createModerationSetup(GroupRole::CoOrganizer);
    $discussion->update(['is_pinned' => true]);

    Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->call('togglePin')
        ->assertHasNoErrors();

    $discussion->refresh();
    expect($discussion->is_pinned)->toBeFalse();
});

it('prevents a regular member from pinning a discussion', function (): void {
    [$user, $group, $organizer, $discussion] = createModerationSetup(GroupRole::Member);

    Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->call('togglePin')
        ->assertForbidden();

    $discussion->refresh();
    expect($discussion->is_pinned)->toBeFalse();
});

// --- Lock / Unlock ---

it('allows a co-organizer to lock a discussion', function (): void {
    [$user, $group, $organizer, $discussion] = createModerationSetup(GroupRole::CoOrganizer);

    Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->call('toggleLock')
        ->assertHasNoErrors();

    $discussion->refresh();
    expect($discussion->is_locked)->toBeTrue();
});

it('allows a co-organizer to unlock a discussion', function (): void {
    [$user, $group, $organizer, $discussion] = createModerationSetup(GroupRole::CoOrganizer);
    $discussion->update(['is_locked' => true]);

    Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->call('toggleLock')
        ->assertHasNoErrors();

    $discussion->refresh();
    expect($discussion->is_locked)->toBeFalse();
});

it('prevents new replies when a discussion is locked', function (): void {
    [$user, $group, $organizer, $discussion] = createModerationSetup(GroupRole::CoOrganizer);

    // Lock it
    Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->call('toggleLock');

    $discussion->refresh();
    expect($discussion->is_locked)->toBeTrue();

    // Another member tries to reply
    $member = User::factory()->create();
    $group->members()->attach($member->id, ['role' => GroupRole::Member->value, 'joined_at' => now()]);

    Livewire::actingAs($member)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->set('body', 'Should not be allowed')
        ->call('addReply')
        ->assertForbidden();

    expect(DiscussionReply::where('discussion_id', $discussion->id)->count())->toBe(0);
});

it('prevents a regular member from locking a discussion', function (): void {
    [$user, $group, $organizer, $discussion] = createModerationSetup(GroupRole::Member);

    Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->call('toggleLock')
        ->assertForbidden();

    $discussion->refresh();
    expect($discussion->is_locked)->toBeFalse();
});

// --- Delete Discussion ---

it('allows a co-organizer to soft delete a discussion', function (): void {
    [$user, $group, $organizer, $discussion] = createModerationSetup(GroupRole::CoOrganizer);

    Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->call('deleteDiscussion')
        ->assertRedirect(route('groups.show', ['group' => $group->slug, 'tab' => 'discussions']));

    expect(Discussion::find($discussion->id))->toBeNull();
    expect(Discussion::withTrashed()->find($discussion->id))->not->toBeNull();
});

it('prevents a regular member from deleting a discussion', function (): void {
    [$user, $group, $organizer, $discussion] = createModerationSetup(GroupRole::Member);

    Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->call('deleteDiscussion')
        ->assertForbidden();

    expect(Discussion::find($discussion->id))->not->toBeNull();
});

// --- Delete Reply ---

it('allows an author to soft delete their own reply', function (): void {
    [$user, $group, $organizer, $discussion] = createModerationSetup(GroupRole::Member);

    $reply = DiscussionReply::factory()->for($discussion)->for($user, 'user')->create();

    Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->call('deleteReply', $reply->id)
        ->assertHasNoErrors();

    expect(DiscussionReply::find($reply->id))->toBeNull();
    expect(DiscussionReply::withTrashed()->find($reply->id))->not->toBeNull();
});

it('allows a co-organizer to delete any reply', function (): void {
    [$user, $group, $organizer, $discussion] = createModerationSetup(GroupRole::CoOrganizer);

    $member = User::factory()->create();
    $group->members()->attach($member->id, ['role' => GroupRole::Member->value, 'joined_at' => now()]);
    $reply = DiscussionReply::factory()->for($discussion)->for($member, 'user')->create();

    Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->call('deleteReply', $reply->id)
        ->assertHasNoErrors();

    expect(DiscussionReply::find($reply->id))->toBeNull();
    expect(DiscussionReply::withTrashed()->find($reply->id))->not->toBeNull();
});

it('prevents a member from deleting another members reply', function (): void {
    [$user, $group, $organizer, $discussion] = createModerationSetup(GroupRole::Member);

    $otherMember = User::factory()->create();
    $group->members()->attach($otherMember->id, ['role' => GroupRole::Member->value, 'joined_at' => now()]);
    $reply = DiscussionReply::factory()->for($discussion)->for($otherMember, 'user')->create();

    Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->call('deleteReply', $reply->id)
        ->assertForbidden();

    expect(DiscussionReply::find($reply->id))->not->toBeNull();
});
