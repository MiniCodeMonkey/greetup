<?php

use App\Enums\AttendanceMode;
use App\Enums\AttendanceResult;
use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Rsvp;
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
    $rsvp = Rsvp::factory()->create();

    expect($rsvp)->toBeInstanceOf(Rsvp::class)
        ->and($rsvp->exists)->toBeTrue()
        ->and($rsvp->status)->toBe(RsvpStatus::Going)
        ->and($rsvp->guest_count)->toBe(0)
        ->and($rsvp->attendance_mode)->toBeNull()
        ->and($rsvp->checked_in)->toBeFalse()
        ->and($rsvp->checked_in_at)->toBeNull()
        ->and($rsvp->checked_in_by)->toBeNull()
        ->and($rsvp->attended)->toBeNull()
        ->and($rsvp->waitlisted_at)->toBeNull();
});

it('has event belongsTo relationship', function (): void {
    $rsvp = Rsvp::factory()->create();

    expect($rsvp->event())->toBeInstanceOf(BelongsTo::class)
        ->and($rsvp->event)->toBeInstanceOf(Event::class);
});

it('has user belongsTo relationship', function (): void {
    $rsvp = Rsvp::factory()->create();

    expect($rsvp->user())->toBeInstanceOf(BelongsTo::class)
        ->and($rsvp->user)->toBeInstanceOf(User::class);
});

it('has checkedInBy belongsTo relationship', function (): void {
    $rsvp = Rsvp::factory()->checkedIn()->create();

    expect($rsvp->checkedInBy())->toBeInstanceOf(BelongsTo::class)
        ->and($rsvp->checkedInBy)->toBeInstanceOf(User::class);
});

it('casts status to RsvpStatus enum', function (): void {
    $rsvp = Rsvp::factory()->create();

    expect($rsvp->status)->toBeInstanceOf(RsvpStatus::class);
});

it('casts attendance_mode to AttendanceMode enum', function (): void {
    $rsvp = Rsvp::factory()->create(['attendance_mode' => 'in_person']);

    expect($rsvp->attendance_mode)->toBeInstanceOf(AttendanceMode::class)
        ->and($rsvp->attendance_mode)->toBe(AttendanceMode::InPerson);
});

it('casts attended to AttendanceResult enum', function (): void {
    $rsvp = Rsvp::factory()->checkedIn()->create();

    expect($rsvp->attended)->toBeInstanceOf(AttendanceResult::class)
        ->and($rsvp->attended)->toBe(AttendanceResult::Attended);
});

it('casts checked_in to boolean', function (): void {
    $rsvp = Rsvp::factory()->create();

    expect($rsvp->checked_in)->toBeBool()->toBeFalse();
});

it('casts guest_count to integer', function (): void {
    $rsvp = Rsvp::factory()->withGuests(3)->create();

    expect($rsvp->guest_count)->toBeInt()->toBe(3);
});

it('casts checked_in_at to datetime', function (): void {
    $rsvp = Rsvp::factory()->checkedIn()->create();

    expect($rsvp->checked_in_at)->toBeInstanceOf(Carbon\Carbon::class);
});

it('casts waitlisted_at to datetime', function (): void {
    $rsvp = Rsvp::factory()->waitlisted()->create();

    expect($rsvp->waitlisted_at)->toBeInstanceOf(Carbon\Carbon::class);
});

it('enforces unique constraint on event_id and user_id', function (): void {
    $event = Event::factory()->create();
    $user = User::factory()->create();

    Rsvp::factory()->create(['event_id' => $event->id, 'user_id' => $user->id]);

    Rsvp::factory()->create(['event_id' => $event->id, 'user_id' => $user->id]);
})->throws(QueryException::class);

it('has going factory state', function (): void {
    $rsvp = Rsvp::factory()->going()->create();

    expect($rsvp->status)->toBe(RsvpStatus::Going);
});

it('has waitlisted factory state', function (): void {
    $rsvp = Rsvp::factory()->waitlisted()->create();

    expect($rsvp->status)->toBe(RsvpStatus::Waitlisted)
        ->and($rsvp->waitlisted_at)->not->toBeNull();
});

it('has notGoing factory state', function (): void {
    $rsvp = Rsvp::factory()->notGoing()->create();

    expect($rsvp->status)->toBe(RsvpStatus::NotGoing);
});

it('has checkedIn factory state', function (): void {
    $rsvp = Rsvp::factory()->checkedIn()->create();

    expect($rsvp->status)->toBe(RsvpStatus::Going)
        ->and($rsvp->checked_in)->toBeTrue()
        ->and($rsvp->checked_in_at)->not->toBeNull()
        ->and($rsvp->checkedInBy)->toBeInstanceOf(User::class)
        ->and($rsvp->attended)->toBe(AttendanceResult::Attended);
});

it('has withGuests factory state', function (): void {
    $rsvp = Rsvp::factory()->withGuests(5)->create();

    expect($rsvp->guest_count)->toBe(5);
});
