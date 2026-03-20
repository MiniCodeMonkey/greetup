<?php

use App\Enums\GroupRole;
use App\Enums\RsvpStatus;
use App\Livewire\EventFeedback;
use App\Models\Event;
use App\Models\Feedback;
use App\Models\Group;
use App\Models\User;
use App\Notifications\NewEventFeedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

function createPastEventWithAttendee(GroupRole $role = GroupRole::Member): array
{
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, ['role' => GroupRole::Organizer->value, 'joined_at' => now()]);

    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->subDay()->addHours(2),
    ]);
    $event->hosts()->attach($organizer->id);

    $member = User::factory()->create();
    $group->members()->attach($member->id, ['role' => $role->value, 'joined_at' => now()]);

    $event->rsvps()->create([
        'user_id' => $member->id,
        'status' => RsvpStatus::Going,
    ]);

    return [$member, $event, $group, $organizer];
}

it('allows an attendee to submit feedback after event ends', function (): void {
    Notification::fake();

    [$member, $event] = createPastEventWithAttendee();

    Livewire::actingAs($member)
        ->test(EventFeedback::class, ['event' => $event])
        ->set('rating', 4)
        ->set('body', 'Great event!')
        ->call('submitFeedback')
        ->assertSet('rating', 0)
        ->assertSet('body', '');

    expect(Feedback::where('event_id', $event->id)->where('user_id', $member->id)->count())->toBe(1);

    $feedback = Feedback::where('event_id', $event->id)->first();
    expect($feedback->rating)->toBe(4);
    expect($feedback->body)->toBe('Great event!');
});

it('allows feedback with rating only (no body)', function (): void {
    Notification::fake();

    [$member, $event] = createPastEventWithAttendee();

    Livewire::actingAs($member)
        ->test(EventFeedback::class, ['event' => $event])
        ->set('rating', 5)
        ->call('submitFeedback');

    $feedback = Feedback::where('event_id', $event->id)->first();
    expect($feedback->rating)->toBe(5);
    expect($feedback->body)->toBeNull();
});

it('rejects duplicate feedback from the same user', function (): void {
    Notification::fake();

    [$member, $event] = createPastEventWithAttendee();

    Feedback::create([
        'event_id' => $event->id,
        'user_id' => $member->id,
        'rating' => 3,
    ]);

    Livewire::actingAs($member)
        ->test(EventFeedback::class, ['event' => $event])
        ->set('rating', 5)
        ->call('submitFeedback')
        ->assertForbidden();

    expect(Feedback::where('event_id', $event->id)->where('user_id', $member->id)->count())->toBe(1);
});

it('rejects feedback for an event that has not yet ended', function (): void {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, ['role' => GroupRole::Organizer->value, 'joined_at' => now()]);

    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHours(2),
    ]);
    $event->hosts()->attach($organizer->id);

    $member = User::factory()->create();
    $group->members()->attach($member->id, ['role' => GroupRole::Member->value, 'joined_at' => now()]);

    $event->rsvps()->create([
        'user_id' => $member->id,
        'status' => RsvpStatus::Going,
    ]);

    Livewire::actingAs($member)
        ->test(EventFeedback::class, ['event' => $event])
        ->set('rating', 4)
        ->call('submitFeedback')
        ->assertForbidden();

    expect(Feedback::where('event_id', $event->id)->count())->toBe(0);
});

it('uses starts_at + 3 hours when ends_at is null', function (): void {
    Notification::fake();

    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, ['role' => GroupRole::Organizer->value, 'joined_at' => now()]);

    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
        'starts_at' => now()->subHours(4),
        'ends_at' => null,
    ]);
    $event->hosts()->attach($organizer->id);

    $member = User::factory()->create();
    $group->members()->attach($member->id, ['role' => GroupRole::Member->value, 'joined_at' => now()]);

    $event->rsvps()->create([
        'user_id' => $member->id,
        'status' => RsvpStatus::Going,
    ]);

    Livewire::actingAs($member)
        ->test(EventFeedback::class, ['event' => $event])
        ->set('rating', 5)
        ->call('submitFeedback');

    expect(Feedback::where('event_id', $event->id)->count())->toBe(1);
});

it('rejects feedback from non-attendee (no RSVP)', function (): void {
    [$member, $event, $group] = createPastEventWithAttendee();

    $nonAttendee = User::factory()->create();
    $group->members()->attach($nonAttendee->id, ['role' => GroupRole::Member->value, 'joined_at' => now()]);

    Livewire::actingAs($nonAttendee)
        ->test(EventFeedback::class, ['event' => $event])
        ->set('rating', 3)
        ->call('submitFeedback')
        ->assertForbidden();

    expect(Feedback::where('event_id', $event->id)->where('user_id', $nonAttendee->id)->count())->toBe(0);
});

it('shows attributed feedback to organizer', function (): void {
    Notification::fake();

    [$member, $event, $group, $organizer] = createPastEventWithAttendee();

    Feedback::create([
        'event_id' => $event->id,
        'user_id' => $member->id,
        'rating' => 4,
        'body' => 'Loved it!',
    ]);

    Livewire::actingAs($organizer)
        ->test(EventFeedback::class, ['event' => $event])
        ->assertSee($member->name)
        ->assertSee('Loved it!')
        ->assertSeeHtml('data-testid="feedback-list-attributed"');
});

it('shows anonymous aggregate to regular member', function (): void {
    Notification::fake();

    [$member, $event, $group, $organizer] = createPastEventWithAttendee();

    $otherMember = User::factory()->create();
    $group->members()->attach($otherMember->id, ['role' => GroupRole::Member->value, 'joined_at' => now()]);
    $event->rsvps()->create([
        'user_id' => $otherMember->id,
        'status' => RsvpStatus::Going,
    ]);

    Feedback::create([
        'event_id' => $event->id,
        'user_id' => $otherMember->id,
        'rating' => 4,
        'body' => 'Great event!',
    ]);

    Livewire::actingAs($member)
        ->test(EventFeedback::class, ['event' => $event])
        ->assertDontSee($otherMember->name)
        ->assertSeeHtml('data-testid="feedback-aggregate"')
        ->assertDontSeeHtml('data-testid="feedback-list-attributed"');
});

it('sends NewEventFeedback notification to event host and organizer', function (): void {
    Notification::fake();

    [$member, $event, $group, $organizer] = createPastEventWithAttendee();

    Livewire::actingAs($member)
        ->test(EventFeedback::class, ['event' => $event])
        ->set('rating', 5)
        ->set('body', 'Amazing!')
        ->call('submitFeedback');

    Notification::assertSentTo($organizer, NewEventFeedback::class);
    Notification::assertNotSentTo($member, NewEventFeedback::class);
});

it('validates rating is required and between 1 and 5', function (): void {
    [$member, $event] = createPastEventWithAttendee();

    Livewire::actingAs($member)
        ->test(EventFeedback::class, ['event' => $event])
        ->set('rating', 0)
        ->call('submitFeedback')
        ->assertHasErrors(['rating']);

    Livewire::actingAs($member)
        ->test(EventFeedback::class, ['event' => $event])
        ->set('rating', 6)
        ->call('submitFeedback')
        ->assertHasErrors(['rating']);
});
