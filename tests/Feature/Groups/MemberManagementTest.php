<?php

use App\Enums\AttendanceResult;
use App\Enums\GroupRole;
use App\Enums\JoinRequestStatus;
use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Group;
use App\Models\GroupJoinRequest;
use App\Models\Rsvp;
use App\Models\User;
use App\Notifications\JoinRequestApproved;
use App\Notifications\JoinRequestDenied;
use App\Notifications\MemberBanned;
use App\Notifications\MemberRemoved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Notification::fake();

    $this->organizer = User::factory()->create();
    $this->group = Group::factory()->create([
        'organizer_id' => $this->organizer->id,
        'requires_approval' => false,
    ]);
    $this->group->members()->attach($this->organizer->id, [
        'role' => GroupRole::Organizer->value,
        'joined_at' => now(),
    ]);

    $this->assistantOrganizer = User::factory()->create();
    $this->group->members()->attach($this->assistantOrganizer->id, [
        'role' => GroupRole::AssistantOrganizer->value,
        'joined_at' => now(),
    ]);
});

// --- Member List Page ---

it('displays the member management page with pagination', function (): void {
    $members = User::factory()->count(25)->create();
    foreach ($members as $member) {
        $this->group->members()->attach($member->id, [
            'role' => GroupRole::Member->value,
            'joined_at' => now(),
        ]);
    }

    $this->actingAs($this->assistantOrganizer)
        ->get(route('groups.manage.members', $this->group))
        ->assertOk()
        ->assertViewIs('groups.manage.members')
        ->assertViewHas('members');
});

it('shows member name, role, joined date, and attendance stats', function (): void {
    $member = User::factory()->create(['name' => 'Test Member']);
    $this->group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now()->subDays(10),
    ]);

    $event = Event::factory()->create(['group_id' => $this->group->id]);

    Rsvp::factory()->create([
        'event_id' => $event->id,
        'user_id' => $member->id,
        'status' => RsvpStatus::Going,
        'attended' => AttendanceResult::Attended,
    ]);

    Rsvp::factory()->create([
        'event_id' => Event::factory()->create(['group_id' => $this->group->id])->id,
        'user_id' => $member->id,
        'status' => RsvpStatus::Going,
        'attended' => AttendanceResult::NoShow,
    ]);

    $response = $this->actingAs($this->assistantOrganizer)
        ->get(route('groups.manage.members', $this->group))
        ->assertOk()
        ->assertSee('Test Member');

    $memberStats = $response->viewData('memberStats');
    expect($memberStats[$member->id]['attended'])->toBe(1)
        ->and($memberStats[$member->id]['no_shows'])->toBe(1);
});

it('filters members by name search', function (): void {
    $alice = User::factory()->create(['name' => 'Alice Smith']);
    $bob = User::factory()->create(['name' => 'Bob Jones']);

    foreach ([$alice, $bob] as $member) {
        $this->group->members()->attach($member->id, [
            'role' => GroupRole::Member->value,
            'joined_at' => now(),
        ]);
    }

    $this->actingAs($this->assistantOrganizer)
        ->get(route('groups.manage.members', ['group' => $this->group, 'search' => 'Alice']))
        ->assertOk()
        ->assertSee('Alice Smith')
        ->assertDontSee('Bob Jones');
});

// --- Remove Member ---

it('allows assistant organizer to remove a member', function (): void {
    $member = User::factory()->create();
    $this->group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->assistantOrganizer)
        ->post(route('groups.manage.members.remove', [$this->group, $member]), [
            'reason' => 'Violated community guidelines',
        ])
        ->assertRedirect(route('groups.manage.members', $this->group))
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('group_members', [
        'group_id' => $this->group->id,
        'user_id' => $member->id,
    ]);

    Notification::assertSentTo($member, MemberRemoved::class, function ($notification) {
        return $notification->reason === 'Violated community guidelines';
    });
});

it('allows removing a member without a reason', function (): void {
    $member = User::factory()->create();
    $this->group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->assistantOrganizer)
        ->post(route('groups.manage.members.remove', [$this->group, $member]))
        ->assertRedirect(route('groups.manage.members', $this->group));

    Notification::assertSentTo($member, MemberRemoved::class);
});

// --- Ban Member ---

it('allows assistant organizer to ban a member with reason and notification', function (): void {
    $member = User::factory()->create();
    $this->group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->assistantOrganizer)
        ->post(route('groups.manage.members.ban', [$this->group, $member]), [
            'reason' => 'Repeated harassment',
        ])
        ->assertRedirect(route('groups.manage.members', $this->group))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('group_members', [
        'group_id' => $this->group->id,
        'user_id' => $member->id,
        'is_banned' => true,
    ]);

    Notification::assertSentTo($member, MemberBanned::class, function ($notification) {
        return $notification->reason === 'Repeated harassment';
    });
});

it('prevents a banned user from rejoining the group', function (): void {
    $member = User::factory()->create();
    $this->group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'is_banned' => true,
        'banned_at' => now(),
        'banned_reason' => 'Spam',
    ]);

    $this->actingAs($member)
        ->post(route('groups.join', $this->group))
        ->assertForbidden();
});

it('requires reason when banning a member', function (): void {
    $member = User::factory()->create();
    $this->group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->assistantOrganizer)
        ->post(route('groups.manage.members.ban', [$this->group, $member]), [
            'reason' => '',
        ])
        ->assertSessionHasErrors('reason');
});

// --- Unban Member ---

it('allows assistant organizer to unban a member', function (): void {
    $member = User::factory()->create();
    $this->group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'is_banned' => true,
        'banned_at' => now(),
        'banned_reason' => 'Spam',
    ]);

    $this->actingAs($this->assistantOrganizer)
        ->post(route('groups.manage.members.unban', [$this->group, $member]))
        ->assertRedirect(route('groups.manage.members', $this->group))
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('group_members', [
        'group_id' => $this->group->id,
        'user_id' => $member->id,
    ]);
});

// --- Authorization ---

it('rejects a regular member from accessing member management', function (): void {
    $member = User::factory()->create();
    $this->group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($member)
        ->get(route('groups.manage.members', $this->group))
        ->assertForbidden();
});

it('rejects a regular member from removing members', function (): void {
    $member = User::factory()->create();
    $target = User::factory()->create();

    foreach ([$member, $target] as $user) {
        $this->group->members()->attach($user->id, [
            'role' => GroupRole::Member->value,
            'joined_at' => now(),
        ]);
    }

    $this->actingAs($member)
        ->post(route('groups.manage.members.remove', [$this->group, $target]))
        ->assertForbidden();
});

it('rejects a regular member from banning members', function (): void {
    $member = User::factory()->create();
    $target = User::factory()->create();

    foreach ([$member, $target] as $user) {
        $this->group->members()->attach($user->id, [
            'role' => GroupRole::Member->value,
            'joined_at' => now(),
        ]);
    }

    $this->actingAs($member)
        ->post(route('groups.manage.members.ban', [$this->group, $target]), [
            'reason' => 'test',
        ])
        ->assertForbidden();
});

// --- CSV Export ---

it('exports member list as CSV with correct columns', function (): void {
    $member = User::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);
    $this->group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now()->subDays(5),
    ]);

    $event = Event::factory()->create(['group_id' => $this->group->id]);

    Rsvp::factory()->create([
        'event_id' => $event->id,
        'user_id' => $member->id,
        'status' => RsvpStatus::Going,
        'attended' => AttendanceResult::Attended,
    ]);

    $event2 = Event::factory()->create(['group_id' => $this->group->id]);
    Rsvp::factory()->create([
        'event_id' => $event2->id,
        'user_id' => $member->id,
        'status' => RsvpStatus::Going,
        'attended' => AttendanceResult::NoShow,
    ]);

    $response = $this->actingAs($this->assistantOrganizer)
        ->get(route('groups.manage.members.export', $this->group))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Name,Email,"Joined Date","Events Attended",No-Shows')
        ->and($csv)->toContain('Jane Doe')
        ->and($csv)->toContain('jane@example.com');
});

// --- Join Request Management ---

it('displays pending join requests page', function (): void {
    $this->group->update(['requires_approval' => true]);

    $requester = User::factory()->create(['name' => 'Pending User']);
    GroupJoinRequest::create([
        'group_id' => $this->group->id,
        'user_id' => $requester->id,
        'status' => JoinRequestStatus::Pending,
    ]);

    $this->actingAs($this->assistantOrganizer)
        ->get(route('groups.manage.requests', $this->group))
        ->assertOk()
        ->assertViewIs('groups.manage.requests')
        ->assertSee('Pending User');
});

it('approves a join request with notification', function (): void {
    $this->group->update(['requires_approval' => true]);

    $requester = User::factory()->create();
    $joinRequest = GroupJoinRequest::create([
        'group_id' => $this->group->id,
        'user_id' => $requester->id,
        'status' => JoinRequestStatus::Pending,
    ]);

    $this->actingAs($this->assistantOrganizer)
        ->post(route('groups.manage.requests.approve', [$this->group, $joinRequest]))
        ->assertRedirect(route('groups.manage.requests', $this->group))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('group_join_requests', [
        'id' => $joinRequest->id,
        'status' => JoinRequestStatus::Approved->value,
    ]);

    $this->assertDatabaseHas('group_members', [
        'group_id' => $this->group->id,
        'user_id' => $requester->id,
    ]);

    Notification::assertSentTo($requester, JoinRequestApproved::class);
});

it('denies a join request with notification', function (): void {
    $this->group->update(['requires_approval' => true]);

    $requester = User::factory()->create();
    $joinRequest = GroupJoinRequest::create([
        'group_id' => $this->group->id,
        'user_id' => $requester->id,
        'status' => JoinRequestStatus::Pending,
    ]);

    $this->actingAs($this->assistantOrganizer)
        ->post(route('groups.manage.requests.deny', [$this->group, $joinRequest]), [
            'reason' => 'Incomplete profile',
        ])
        ->assertRedirect(route('groups.manage.requests', $this->group))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('group_join_requests', [
        'id' => $joinRequest->id,
        'status' => JoinRequestStatus::Denied->value,
        'denial_reason' => 'Incomplete profile',
    ]);

    Notification::assertSentTo($requester, JoinRequestDenied::class);
});
