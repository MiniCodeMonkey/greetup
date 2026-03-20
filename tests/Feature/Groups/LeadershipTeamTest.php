<?php

use App\Enums\GroupRole;
use App\Models\Group;
use App\Models\User;
use App\Notifications\RoleChanged;
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
    ]);
    $this->group->members()->attach($this->organizer->id, [
        'role' => GroupRole::Organizer->value,
        'joined_at' => now(),
    ]);

    $this->coOrganizer = User::factory()->create();
    $this->group->members()->attach($this->coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);
});

// --- Page Display ---

it('displays the leadership team management page', function (): void {
    $this->actingAs($this->coOrganizer)
        ->get(route('groups.manage.team', $this->group))
        ->assertOk()
        ->assertViewIs('groups.manage.team')
        ->assertViewHas('leadershipMembers')
        ->assertViewHas('regularMembers');
});

it('shows current leadership members', function (): void {
    $eventOrganizer = User::factory()->create(['name' => 'Event Lead']);
    $this->group->members()->attach($eventOrganizer->id, [
        'role' => GroupRole::EventOrganizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->coOrganizer)
        ->get(route('groups.manage.team', $this->group))
        ->assertOk()
        ->assertSee('Event Lead')
        ->assertSee('Event organizer');
});

// --- Promote Member ---

it('allows organizer to promote a member to event_organizer', function (): void {
    $member = User::factory()->create(['name' => 'Promoted User']);
    $this->group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->organizer)
        ->post(route('groups.manage.team.update-role', [$this->group, $member]), [
            'role' => 'event_organizer',
        ])
        ->assertRedirect(route('groups.manage.team', $this->group))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('group_members', [
        'group_id' => $this->group->id,
        'user_id' => $member->id,
        'role' => 'event_organizer',
    ]);

    Notification::assertSentTo($member, RoleChanged::class, function ($notification) {
        return $notification->oldRole === GroupRole::Member
            && $notification->newRole === GroupRole::EventOrganizer;
    });
});

it('allows organizer to promote a member to assistant_organizer', function (): void {
    $member = User::factory()->create();
    $this->group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->organizer)
        ->post(route('groups.manage.team.update-role', [$this->group, $member]), [
            'role' => 'assistant_organizer',
        ])
        ->assertRedirect(route('groups.manage.team', $this->group));

    $this->assertDatabaseHas('group_members', [
        'group_id' => $this->group->id,
        'user_id' => $member->id,
        'role' => 'assistant_organizer',
    ]);
});

it('allows organizer to promote a member to co_organizer', function (): void {
    $member = User::factory()->create();
    $this->group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->organizer)
        ->post(route('groups.manage.team.update-role', [$this->group, $member]), [
            'role' => 'co_organizer',
        ])
        ->assertRedirect(route('groups.manage.team', $this->group));

    $this->assertDatabaseHas('group_members', [
        'group_id' => $this->group->id,
        'user_id' => $member->id,
        'role' => 'co_organizer',
    ]);
});

it('allows co-organizer to promote a member to event_organizer', function (): void {
    $member = User::factory()->create();
    $this->group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->coOrganizer)
        ->post(route('groups.manage.team.update-role', [$this->group, $member]), [
            'role' => 'event_organizer',
        ])
        ->assertRedirect(route('groups.manage.team', $this->group));

    $this->assertDatabaseHas('group_members', [
        'group_id' => $this->group->id,
        'user_id' => $member->id,
        'role' => 'event_organizer',
    ]);
});

// --- Demote Member ---

it('allows organizer to demote a leadership member to lower role', function (): void {
    $assistant = User::factory()->create();
    $this->group->members()->attach($assistant->id, [
        'role' => GroupRole::AssistantOrganizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->organizer)
        ->post(route('groups.manage.team.update-role', [$this->group, $assistant]), [
            'role' => 'event_organizer',
        ])
        ->assertRedirect(route('groups.manage.team', $this->group));

    $this->assertDatabaseHas('group_members', [
        'group_id' => $this->group->id,
        'user_id' => $assistant->id,
        'role' => 'event_organizer',
    ]);

    Notification::assertSentTo($assistant, RoleChanged::class, function ($notification) {
        return $notification->oldRole === GroupRole::AssistantOrganizer
            && $notification->newRole === GroupRole::EventOrganizer;
    });
});

it('allows organizer to demote leadership member to regular member', function (): void {
    $eventOrganizer = User::factory()->create();
    $this->group->members()->attach($eventOrganizer->id, [
        'role' => GroupRole::EventOrganizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->organizer)
        ->post(route('groups.manage.team.update-role', [$this->group, $eventOrganizer]), [
            'role' => 'member',
        ])
        ->assertRedirect(route('groups.manage.team', $this->group));

    $this->assertDatabaseHas('group_members', [
        'group_id' => $this->group->id,
        'user_id' => $eventOrganizer->id,
        'role' => 'member',
    ]);
});

it('allows co-organizer to demote assistant_organizer to member', function (): void {
    $assistant = User::factory()->create();
    $this->group->members()->attach($assistant->id, [
        'role' => GroupRole::AssistantOrganizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->coOrganizer)
        ->post(route('groups.manage.team.update-role', [$this->group, $assistant]), [
            'role' => 'member',
        ])
        ->assertRedirect(route('groups.manage.team', $this->group));

    $this->assertDatabaseHas('group_members', [
        'group_id' => $this->group->id,
        'user_id' => $assistant->id,
        'role' => 'member',
    ]);
});

// --- Co-organizer Limitations ---

it('prevents co-organizer from promoting anyone to co_organizer', function (): void {
    $member = User::factory()->create();
    $this->group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->coOrganizer)
        ->post(route('groups.manage.team.update-role', [$this->group, $member]), [
            'role' => 'co_organizer',
        ])
        ->assertForbidden();
});

it('prevents co-organizer from demoting another co_organizer', function (): void {
    $otherCoOrganizer = User::factory()->create();
    $this->group->members()->attach($otherCoOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->coOrganizer)
        ->post(route('groups.manage.team.update-role', [$this->group, $otherCoOrganizer]), [
            'role' => 'member',
        ])
        ->assertForbidden();
});

// --- Cannot Change Organizer ---

it('prevents changing the primary organizer role', function (): void {
    $this->actingAs($this->organizer)
        ->post(route('groups.manage.team.update-role', [$this->group, $this->organizer]), [
            'role' => 'co_organizer',
        ])
        ->assertForbidden();
});

// --- Authorization ---

it('rejects a regular member from accessing the leadership page', function (): void {
    $member = User::factory()->create();
    $this->group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($member)
        ->get(route('groups.manage.team', $this->group))
        ->assertForbidden();
});

it('rejects a regular member from changing roles', function (): void {
    $member = User::factory()->create();
    $target = User::factory()->create();

    foreach ([$member, $target] as $user) {
        $this->group->members()->attach($user->id, [
            'role' => GroupRole::Member->value,
            'joined_at' => now(),
        ]);
    }

    $this->actingAs($member)
        ->post(route('groups.manage.team.update-role', [$this->group, $target]), [
            'role' => 'event_organizer',
        ])
        ->assertForbidden();
});

// --- Notification ---

it('sends RoleChanged notification to affected member', function (): void {
    $member = User::factory()->create();
    $this->group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->organizer)
        ->post(route('groups.manage.team.update-role', [$this->group, $member]), [
            'role' => 'event_organizer',
        ]);

    Notification::assertSentTo($member, RoleChanged::class, function ($notification) {
        return $notification->group->id === $this->group->id
            && $notification->oldRole === GroupRole::Member
            && $notification->newRole === GroupRole::EventOrganizer;
    });
});

// --- Validation ---

it('rejects invalid role values', function (): void {
    $member = User::factory()->create();
    $this->group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->organizer)
        ->post(route('groups.manage.team.update-role', [$this->group, $member]), [
            'role' => 'organizer',
        ])
        ->assertSessionHasErrors('role');
});
