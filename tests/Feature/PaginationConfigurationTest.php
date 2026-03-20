<?php

use App\Livewire\AttendeeManager;
use App\Livewire\CommentThread;
use App\Livewire\ConversationView;
use App\Livewire\DiscussionThread;
use App\Livewire\ExplorePage;
use App\Livewire\GroupSearchPage;
use App\Livewire\NotificationDropdown;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\DirectMessage;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\Event;
use App\Models\Group;
use App\Models\Report;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->withoutVite();
});

function createPaginationGroup(array $attributes = []): Group
{
    $organizer = User::factory()->create();

    return Group::factory()->create(array_merge([
        'organizer_id' => $organizer->id,
    ], $attributes));
}

function createPaginationEvent(Group $group, array $attributes = []): Event
{
    return Event::factory()->published()->create(array_merge([
        'group_id' => $group->id,
        'created_by' => $group->organizer_id,
        'starts_at' => now()->addDays(5),
    ], $attributes));
}

// Spec 5.9.3: Explore page — 12 items, cursor pagination (infinite scroll via Livewire)
it('paginates explore page events at 12 per page with infinite scroll', function (): void {
    $group = createPaginationGroup();

    Event::factory()->published()->count(15)->create([
        'group_id' => $group->id,
        'created_by' => $group->organizer_id,
        'starts_at' => now()->addDays(5),
    ]);

    $component = Livewire::test(ExplorePage::class);

    $component->assertSet('perPage', 12);
    $component->assertSet('page', 1);
    $component->assertSet('hasMorePages', true);

    // Load more triggers page increment (infinite scroll)
    $component->call('loadMore');
    $component->assertSet('page', 2);
});

// Spec 5.9.3: Group search results — 12 items, cursor pagination
it('paginates group search results at 12 per page with infinite scroll', function (): void {
    Group::factory()->count(15)->create();

    $component = Livewire::test(GroupSearchPage::class);

    $component->assertSet('perPage', 12);
    $component->assertSet('page', 1);
    $component->assertSet('hasMorePages', true);

    $component->call('loadMore');
    $component->assertSet('page', 2);
});

// Spec 5.9.3: Event attendee list — 20 items, standard pagination
it('paginates event attendee list at 20 per page with standard pagination', function (): void {
    $group = createPaginationGroup();
    $event = createPaginationEvent($group);

    Rsvp::factory()->count(25)->create([
        'event_id' => $event->id,
    ]);

    $component = Livewire::actingAs($event->creator)
        ->test(AttendeeManager::class, ['event' => $event]);

    $rsvps = $component->viewData('rsvps');
    expect($rsvps->perPage())->toBe(20);
});

// Spec 5.9.3: Group member list — 20 items, standard pagination
it('paginates group member list at 20 per page with standard pagination', function (): void {
    $group = createPaginationGroup();

    // Organizer must be a member with organizer role for groupRole middleware
    $group->members()->attach($group->organizer_id, ['role' => 'organizer']);

    $members = User::factory()->count(25)->create();
    foreach ($members as $member) {
        $group->members()->attach($member->id, ['role' => 'member']);
    }

    $response = $this->actingAs($group->organizer)
        ->get(route('groups.manage.members', $group));

    $response->assertOk();
    $viewMembers = $response->viewData('members');
    expect($viewMembers->perPage())->toBe(20);
});

// Spec 5.9.3: Discussion list — 15 items, standard pagination
it('paginates discussion list at 15 per page with standard pagination', function (): void {
    $group = createPaginationGroup();
    $user = User::factory()->create();
    $group->members()->attach($user->id, ['role' => 'member']);

    Discussion::factory()->count(20)->create([
        'group_id' => $group->id,
        'user_id' => $user->id,
    ]);

    $response = $this->get(route('groups.show', ['group' => $group->slug, 'tab' => 'discussions']));

    $response->assertOk();
    $discussions = $response->viewData('discussions');
    expect($discussions->perPage())->toBe(15);
});

// Spec 5.9.3: Discussion replies — 20 items, standard pagination
it('paginates discussion replies at 20 per page with standard pagination', function (): void {
    $group = createPaginationGroup();
    $user = User::factory()->create();
    $group->members()->attach($user->id, ['role' => 'member']);

    $discussion = Discussion::factory()->create([
        'group_id' => $group->id,
        'user_id' => $user->id,
    ]);

    DiscussionReply::factory()->count(25)->create([
        'discussion_id' => $discussion->id,
        'user_id' => $user->id,
    ]);

    $component = Livewire::test(DiscussionThread::class, ['discussion' => $discussion]);

    $replies = $component->viewData('replies');
    expect($replies->perPage())->toBe(20);
});

// Spec 5.9.3: Event comments — 15 items, standard pagination
it('paginates event comments at 15 per page with standard pagination', function (): void {
    $group = createPaginationGroup();
    $event = createPaginationEvent($group);

    Comment::factory()->count(20)->create([
        'event_id' => $event->id,
    ]);

    $component = Livewire::test(CommentThread::class, ['event' => $event]);

    $comments = $component->viewData('comments');
    expect($comments->perPage())->toBe(15);
});

// Spec 5.9.3: DM conversation list — 20 items, standard pagination
it('paginates DM conversation list at 20 per page with standard pagination', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 25; $i++) {
        $otherUser = User::factory()->create();
        $conversation = Conversation::factory()->create();
        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);
        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $otherUser->id,
        ]);
        DirectMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $otherUser->id,
        ]);
    }

    $response = $this->actingAs($user)->get(route('messages.index'));

    $response->assertOk();
    $conversations = $response->viewData('conversations');
    expect($conversations->perPage())->toBe(20);
});

// Spec 5.9.3: DM messages — 30 items, cursor pagination
it('paginates DM messages at 30 per page with cursor pagination', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $conversation = Conversation::factory()->create();
    ConversationParticipant::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
    ]);
    ConversationParticipant::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $otherUser->id,
    ]);

    DirectMessage::factory()->count(35)->create([
        'conversation_id' => $conversation->id,
        'user_id' => $otherUser->id,
    ]);

    $component = Livewire::actingAs($user)
        ->test(ConversationView::class, ['conversation' => $conversation]);

    $messages = $component->viewData('messages');
    expect($messages->perPage())->toBe(30);
});

// Spec 5.9.3: Admin user list — 25 items, standard pagination
it('paginates admin user list at 25 per page with standard pagination', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    User::factory()->count(30)->create();

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk();
    $users = $response->viewData('users');
    expect($users->perPage())->toBe(25);
});

// Spec 5.9.3: Admin group list — 25 items, standard pagination
it('paginates admin group list at 25 per page with standard pagination', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Group::factory()->count(30)->create();

    $response = $this->actingAs($admin)->get(route('admin.groups.index'));

    $response->assertOk();
    $groups = $response->viewData('groups');
    expect($groups->perPage())->toBe(25);
});

// Spec 5.9.3: Admin report list — 25 items, standard pagination
it('paginates admin report list at 25 per page with standard pagination', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Report::factory()->count(30)->create();

    $response = $this->actingAs($admin)->get(route('admin.reports.index'));

    $response->assertOk();
    $reports = $response->viewData('reports');
    expect($reports->perPage())->toBe(25);
});

// Spec 5.9.3: Notification dropdown — 10 items, load more button
it('paginates notification dropdown at 10 per page with load more button', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(NotificationDropdown::class);

    $component->assertSet('perPage', 10);
});
