<?php

use App\Enums\GroupRole;
use App\Livewire\DiscussionThread;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\Group;
use App\Models\User;
use App\Notifications\NewDiscussionReply;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

function createDiscussionReplySetup(GroupRole $role = GroupRole::Member, bool $locked = false): array
{
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $user = User::factory()->create();
    $group->members()->attach($user->id, ['role' => $role->value, 'joined_at' => now()]);
    $group->members()->attach($organizer->id, ['role' => GroupRole::Organizer->value, 'joined_at' => now()]);

    $discussion = Discussion::factory()->for($group)->for($organizer, 'user')->create([
        'is_locked' => $locked,
    ]);

    return [$user, $group, $organizer, $discussion];
}

it('allows a group member to reply to a discussion', function (): void {
    Notification::fake();

    [$user, $group, $organizer, $discussion] = createDiscussionReplySetup();

    Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->set('body', 'This is my reply with **markdown**')
        ->call('addReply')
        ->assertHasNoErrors();

    $reply = DiscussionReply::where('discussion_id', $discussion->id)->first();
    expect($reply)->not->toBeNull();
    expect($reply->user_id)->toBe($user->id);
    expect($reply->body)->toBe('This is my reply with **markdown**');
    expect($reply->body_html)->toContain('<strong>markdown</strong>');
});

it('updates last_activity_at when a reply is added', function (): void {
    Notification::fake();

    [$user, $group, $organizer, $discussion] = createDiscussionReplySetup();

    $originalActivityAt = $discussion->last_activity_at;

    $this->travel(1)->hours();

    Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->set('body', 'A new reply')
        ->call('addReply');

    $discussion->refresh();
    expect($discussion->last_activity_at->gt($originalActivityAt))->toBeTrue();
});

it('rejects replies to a locked discussion', function (): void {
    Notification::fake();

    [$user, $group, $organizer, $discussion] = createDiscussionReplySetup(locked: true);

    Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->set('body', 'Cannot reply to locked')
        ->call('addReply')
        ->assertForbidden();

    expect(DiscussionReply::where('discussion_id', $discussion->id)->count())->toBe(0);
});

it('sends NewDiscussionReply notification to discussion author and previous repliers', function (): void {
    Notification::fake();

    [$user, $group, $organizer, $discussion] = createDiscussionReplySetup();

    $previousReplier = User::factory()->create();
    $group->members()->attach($previousReplier->id, ['role' => GroupRole::Member->value, 'joined_at' => now()]);

    DiscussionReply::factory()->for($discussion)->for($previousReplier, 'user')->create();

    Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->set('body', 'My reply here')
        ->call('addReply');

    // Discussion author (organizer) should be notified
    Notification::assertSentTo($organizer, NewDiscussionReply::class);

    // Previous replier should be notified
    Notification::assertSentTo($previousReplier, NewDiscussionReply::class);

    // The replier themselves should NOT be notified
    Notification::assertNotSentTo($user, NewDiscussionReply::class);
});

it('does not notify the replier when they are also the discussion author', function (): void {
    Notification::fake();

    [$user, $group, $organizer, $discussion] = createDiscussionReplySetup();

    // Organizer is the discussion author — reply as organizer
    Livewire::actingAs($organizer)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->set('body', 'Author replying to own discussion')
        ->call('addReply');

    Notification::assertNotSentTo($organizer, NewDiscussionReply::class);
});

it('paginates replies at 20 per page', function (): void {
    Notification::fake();

    [$user, $group, $organizer, $discussion] = createDiscussionReplySetup();

    DiscussionReply::factory()
        ->count(25)
        ->for($discussion)
        ->for($user, 'user')
        ->create();

    $component = Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion]);

    $replies = $component->viewData('replies');
    expect($replies)->toHaveCount(20);
    expect($replies->total())->toBe(25);
});

it('displays replies in chronological order', function (): void {
    Notification::fake();

    [$user, $group, $organizer, $discussion] = createDiscussionReplySetup();

    $first = DiscussionReply::factory()->for($discussion)->for($user, 'user')->create([
        'body' => 'First reply',
        'body_html' => '<p>First reply</p>',
        'created_at' => now()->subHours(2),
    ]);

    $second = DiscussionReply::factory()->for($discussion)->for($user, 'user')->create([
        'body' => 'Second reply',
        'body_html' => '<p>Second reply</p>',
        'created_at' => now()->subHour(),
    ]);

    Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->assertSeeInOrder(['First reply', 'Second reply']);
});

it('validates reply body is required', function (): void {
    Notification::fake();

    [$user, $group, $organizer, $discussion] = createDiscussionReplySetup();

    Livewire::actingAs($user)
        ->test(DiscussionThread::class, ['discussion' => $discussion])
        ->set('body', '')
        ->call('addReply')
        ->assertHasErrors(['body' => 'required']);
});

it('shows the discussion show page with thread', function (): void {
    [$user, $group, $organizer, $discussion] = createDiscussionReplySetup();

    $response = $this->actingAs($user)
        ->get(route('discussions.show', [$group, $discussion]));

    $response->assertStatus(200);
    $response->assertSee($discussion->title);
    $response->assertSeeLivewire(DiscussionThread::class);
});
