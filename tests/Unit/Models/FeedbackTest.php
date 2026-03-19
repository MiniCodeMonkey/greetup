<?php

use App\Models\Event;
use App\Models\Feedback;
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
    $feedback = Feedback::factory()->create();

    expect($feedback)->toBeInstanceOf(Feedback::class)
        ->and($feedback->exists)->toBeTrue()
        ->and($feedback->rating)->toBeInt()
        ->and($feedback->rating)->toBeGreaterThanOrEqual(1)
        ->and($feedback->rating)->toBeLessThanOrEqual(5);
});

it('has event belongsTo relationship', function (): void {
    $feedback = Feedback::factory()->create();

    expect($feedback->event())->toBeInstanceOf(BelongsTo::class)
        ->and($feedback->event)->toBeInstanceOf(Event::class);
});

it('has user belongsTo relationship', function (): void {
    $feedback = Feedback::factory()->create();

    expect($feedback->user())->toBeInstanceOf(BelongsTo::class)
        ->and($feedback->user)->toBeInstanceOf(User::class);
});

it('casts rating to integer', function (): void {
    $feedback = Feedback::factory()->create();

    expect($feedback->rating)->toBeInt();
});

it('enforces unique constraint on event_id and user_id', function (): void {
    $event = Event::factory()->create();
    $user = User::factory()->create();

    Feedback::factory()->create(['event_id' => $event->id, 'user_id' => $user->id]);

    Feedback::factory()->create(['event_id' => $event->id, 'user_id' => $user->id]);
})->throws(QueryException::class);

it('allows nullable body', function (): void {
    $feedback = Feedback::factory()->create(['body' => null]);

    expect($feedback->body)->toBeNull();
});

it('uses the event_feedback table', function (): void {
    $feedback = Feedback::factory()->create();

    expect($feedback->getTable())->toBe('event_feedback');
});
