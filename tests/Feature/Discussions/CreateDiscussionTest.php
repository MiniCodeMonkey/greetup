<?php

use App\Enums\GroupRole;
use App\Models\Discussion;
use App\Models\Group;
use App\Models\User;
use App\Notifications\NewDiscussion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

function createGroupWithDiscussionMember(GroupRole $role = GroupRole::Member): array
{
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $user = User::factory()->create();
    $group->members()->attach($user->id, ['role' => $role->value, 'joined_at' => now()]);

    return [$user, $group, $organizer];
}

it('creates a discussion with title and body', function (): void {
    Notification::fake();

    [$user, $group] = createGroupWithDiscussionMember();

    $response = $this->actingAs($user)
        ->post(route('discussions.store', $group), [
            'title' => 'Best Laravel Packages',
            'body' => '## My Favorites\n\nWhat are your **favorite** Laravel packages?',
        ]);

    $discussion = Discussion::where('title', 'Best Laravel Packages')->first();
    expect($discussion)->not->toBeNull();

    $response->assertRedirect(route('groups.show', ['group' => $group->slug, 'tab' => 'discussions']));

    expect($discussion->group_id)->toBe($group->id);
    expect($discussion->user_id)->toBe($user->id);
    expect($discussion->body)->toBe('## My Favorites\n\nWhat are your **favorite** Laravel packages?');
    expect($discussion->body_html)->toContain('My Favorites');
    expect($discussion->last_activity_at)->not->toBeNull();
});

it('auto-generates a slug from the title', function (): void {
    Notification::fake();

    [$user, $group] = createGroupWithDiscussionMember();

    $this->actingAs($user)
        ->post(route('discussions.store', $group), [
            'title' => 'Welcome to Our Group',
            'body' => 'Hello everyone!',
        ]);

    $discussion = Discussion::where('title', 'Welcome to Our Group')->first();
    expect($discussion)->not->toBeNull();
    expect($discussion->slug)->toBe('welcome-to-our-group');
});

it('handles slug collisions within the same group', function (): void {
    Notification::fake();

    [$user, $group] = createGroupWithDiscussionMember();

    $this->actingAs($user)
        ->post(route('discussions.store', $group), [
            'title' => 'Duplicate Title',
            'body' => 'First discussion.',
        ]);

    $this->actingAs($user)
        ->post(route('discussions.store', $group), [
            'title' => 'Duplicate Title',
            'body' => 'Second discussion.',
        ]);

    $discussions = Discussion::where('title', 'Duplicate Title')
        ->where('group_id', $group->id)
        ->get();

    expect($discussions)->toHaveCount(2);

    $slugs = $discussions->pluck('slug')->toArray();
    expect($slugs[0])->not->toBe($slugs[1]);
    expect($slugs)->toContain('duplicate-title');
});

it('lists pinned discussions first, then by last_activity_at descending', function (): void {
    Notification::fake();

    [$user, $group] = createGroupWithDiscussionMember();

    $older = Discussion::factory()->for($group)->for($user, 'user')->create([
        'title' => 'Older Discussion',
        'last_activity_at' => now()->subDays(5),
        'is_pinned' => false,
    ]);

    $newer = Discussion::factory()->for($group)->for($user, 'user')->create([
        'title' => 'Newer Discussion',
        'last_activity_at' => now()->subDay(),
        'is_pinned' => false,
    ]);

    $pinned = Discussion::factory()->for($group)->for($user, 'user')->create([
        'title' => 'Pinned Discussion',
        'last_activity_at' => now()->subDays(10),
        'is_pinned' => true,
    ]);

    $response = $this->actingAs($user)
        ->get(route('groups.show', ['group' => $group->slug, 'tab' => 'discussions']));

    $response->assertStatus(200);
    $response->assertSeeInOrder([
        'Pinned Discussion',
        'Newer Discussion',
        'Older Discussion',
    ]);
});

it('dispatches NewDiscussion notification to group members', function (): void {
    Notification::fake();

    [$user, $group, $organizer] = createGroupWithDiscussionMember();
    $group->members()->attach($organizer->id, ['role' => GroupRole::Organizer->value, 'joined_at' => now()]);

    $member = User::factory()->create();
    $group->members()->attach($member->id, ['role' => GroupRole::Member->value, 'joined_at' => now()]);

    $this->actingAs($user)
        ->post(route('discussions.store', $group), [
            'title' => 'Important Announcement',
            'body' => 'Please read this!',
        ]);

    Notification::assertSentTo($member, NewDiscussion::class);
    Notification::assertSentTo($organizer, NewDiscussion::class);
    Notification::assertNotSentTo($user, NewDiscussion::class);
});

it('paginates discussions at 15 per page', function (): void {
    Notification::fake();

    [$user, $group] = createGroupWithDiscussionMember();

    Discussion::factory()
        ->count(20)
        ->for($group)
        ->for($user, 'user')
        ->create(['last_activity_at' => now()]);

    $response = $this->actingAs($user)
        ->get(route('groups.show', ['group' => $group->slug, 'tab' => 'discussions']));

    $response->assertStatus(200);

    // Page 1 should have 15 discussions
    $viewDiscussions = $response->viewData('discussions');
    expect($viewDiscussions)->toHaveCount(15);
    expect($viewDiscussions->total())->toBe(20);
});

it('requires authentication to create a discussion', function (): void {
    $group = Group::factory()->create();

    $this->post(route('discussions.store', $group), [
        'title' => 'Test',
        'body' => 'Test body',
    ])->assertRedirect(route('login'));
});

it('requires group membership to create a discussion', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('discussions.store', $group), [
            'title' => 'Test',
            'body' => 'Test body',
        ])
        ->assertStatus(403);
});

it('validates required fields', function (): void {
    [$user, $group] = createGroupWithDiscussionMember();

    $this->actingAs($user)
        ->post(route('discussions.store', $group), [])
        ->assertSessionHasErrors(['title', 'body']);
});

it('renders markdown body to body_html', function (): void {
    Notification::fake();

    [$user, $group] = createGroupWithDiscussionMember();

    $this->actingAs($user)
        ->post(route('discussions.store', $group), [
            'title' => 'Markdown Test',
            'body' => '**Bold text** and [a link](https://example.com)',
        ]);

    $discussion = Discussion::where('title', 'Markdown Test')->first();
    expect($discussion)->not->toBeNull();
    expect($discussion->body_html)->toContain('<strong>Bold text</strong>');
    expect($discussion->body_html)->toContain('a link');
});
