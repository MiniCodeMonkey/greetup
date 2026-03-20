<?php

use App\Enums\GroupRole;
use App\Models\Event;
use App\Models\Group;
use App\Models\GroupNotificationMute;
use App\Models\Rsvp;
use App\Models\User;
use App\Notifications\NewEvent;
use App\Notifications\PromotedFromWaitlist;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->service = new NotificationService;
    Mail::fake();
});

it('suppresses NewEvent notification when group is muted', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $group->members()->attach($user->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    GroupNotificationMute::create([
        'user_id' => $user->id,
        'group_id' => $group->id,
        'created_at' => now(),
    ]);

    $event = Event::factory()->published()->create(['group_id' => $group->id]);
    $notification = new NewEvent($event, $group);

    $result = $this->service->dispatch($user, $notification, ['group_id' => $group->id]);

    expect($result)->toBeFalse();
    expect($user->notifications()->count())->toBe(0);
});

it('does NOT suppress PromotedFromWaitlist notification when group is muted', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create(['group_id' => $group->id]);
    $group->members()->attach($user->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    GroupNotificationMute::create([
        'user_id' => $user->id,
        'group_id' => $group->id,
        'created_at' => now(),
    ]);

    $rsvp = Rsvp::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $notification = new PromotedFromWaitlist($event, $rsvp);

    $result = $this->service->dispatch($user, $notification, ['group_id' => $group->id]);

    expect($result)->toBeTrue();
    expect($user->notifications()->where('type', PromotedFromWaitlist::class)->count())->toBe(1);
});

it('creates a mute record when toggling mute on', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $group->members()->attach($user->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->post(route('groups.toggle-mute', $group));

    $response->assertRedirect(route('groups.show', $group));
    expect(GroupNotificationMute::where('user_id', $user->id)->where('group_id', $group->id)->exists())->toBeTrue();
});

it('deletes the mute record when toggling mute off', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $group->members()->attach($user->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    GroupNotificationMute::create([
        'user_id' => $user->id,
        'group_id' => $group->id,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->post(route('groups.toggle-mute', $group));

    $response->assertRedirect(route('groups.show', $group));
    expect(GroupNotificationMute::where('user_id', $user->id)->where('group_id', $group->id)->exists())->toBeFalse();
});

it('shows mute toggle on group page for members', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $group->members()->attach($user->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('groups.show', $group));

    $response->assertStatus(200);
    $response->assertSee('Mute Notifications');
});

it('shows unmute toggle when group is already muted', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $group->members()->attach($user->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    GroupNotificationMute::create([
        'user_id' => $user->id,
        'group_id' => $group->id,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('groups.show', $group));

    $response->assertStatus(200);
    $response->assertSee('Unmute Notifications');
});
