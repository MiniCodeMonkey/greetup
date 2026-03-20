<?php

use App\Http\Controllers\Settings\SettingsController;
use App\Models\Event;
use App\Models\NotificationPreference;
use App\Models\Rsvp;
use App\Models\User;
use App\Notifications\PromotedFromWaitlist;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('displays all 22 notification types with toggles on the notifications settings page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get('/settings?section=notifications');

    $response->assertStatus(200)
        ->assertSee('Notification Preferences')
        ->assertSee('Save notification preferences');

    foreach (SettingsController::NOTIFICATION_TYPES as $type => $config) {
        $response->assertSee($config['label']);
    }

    expect(count(SettingsController::NOTIFICATION_TYPES))->toBe(22);
});

it('displays email and web toggle columns for notification types', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get('/settings?section=notifications');

    $response->assertStatus(200)
        ->assertSee('Web')
        ->assertSee('Email');
});

it('saves notification preferences when toggling', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/notifications', [
            'preferences' => [
                'App\Notifications\WelcomeToGroup' => ['email' => '0', 'web' => '1'],
                'App\Notifications\NewEvent' => ['email' => '1', 'web' => '0'],
            ],
        ])
        ->assertRedirect('/settings?section=notifications')
        ->assertSessionHas('status', 'Notification preferences updated successfully.');

    expect(NotificationPreference::where('user_id', $user->id)->count())->toBe(4);

    $welcomeEmail = NotificationPreference::where('user_id', $user->id)
        ->where('type', 'App\Notifications\WelcomeToGroup')
        ->where('channel', 'email')
        ->first();
    expect($welcomeEmail->enabled)->toBeFalse();

    $welcomeWeb = NotificationPreference::where('user_id', $user->id)
        ->where('type', 'App\Notifications\WelcomeToGroup')
        ->where('channel', 'web')
        ->first();
    expect($welcomeWeb->enabled)->toBeTrue();

    $newEventEmail = NotificationPreference::where('user_id', $user->id)
        ->where('type', 'App\Notifications\NewEvent')
        ->where('channel', 'email')
        ->first();
    expect($newEventEmail->enabled)->toBeTrue();

    $newEventWeb = NotificationPreference::where('user_id', $user->id)
        ->where('type', 'App\Notifications\NewEvent')
        ->where('channel', 'web')
        ->first();
    expect($newEventWeb->enabled)->toBeFalse();
});

it('updates existing preferences when toggling again', function () {
    $user = User::factory()->create();

    NotificationPreference::create([
        'user_id' => $user->id,
        'type' => 'App\Notifications\WelcomeToGroup',
        'channel' => 'email',
        'enabled' => true,
    ]);

    $this->actingAs($user)
        ->put('/settings/notifications', [
            'preferences' => [
                'App\Notifications\WelcomeToGroup' => ['email' => '0', 'web' => '1'],
            ],
        ])
        ->assertRedirect('/settings?section=notifications');

    $pref = NotificationPreference::where('user_id', $user->id)
        ->where('type', 'App\Notifications\WelcomeToGroup')
        ->where('channel', 'email')
        ->first();

    expect($pref->enabled)->toBeFalse();

    // Should not create duplicate records
    expect(NotificationPreference::where('user_id', $user->id)
        ->where('type', 'App\Notifications\WelcomeToGroup')
        ->where('channel', 'email')
        ->count())->toBe(1);
});

it('reflects saved preferences on the notifications settings page', function () {
    $user = User::factory()->create();

    NotificationPreference::create([
        'user_id' => $user->id,
        'type' => 'App\Notifications\NewEvent',
        'channel' => 'email',
        'enabled' => false,
    ]);

    $response = $this->actingAs($user)
        ->get('/settings?section=notifications');

    $response->assertStatus(200)
        ->assertSee('New Event');
});

it('ignores invalid notification types in the request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/notifications', [
            'preferences' => [
                'App\Notifications\FakeNotification' => ['email' => '0'],
                'App\Notifications\WelcomeToGroup' => ['email' => '1', 'web' => '1'],
            ],
        ])
        ->assertRedirect('/settings?section=notifications');

    expect(NotificationPreference::where('user_id', $user->id)
        ->where('type', 'App\Notifications\FakeNotification')
        ->count())->toBe(0);

    expect(NotificationPreference::where('user_id', $user->id)
        ->where('type', 'App\Notifications\WelcomeToGroup')
        ->count())->toBe(2);
});

it('does not save web channel for notification types that only support email', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/notifications', [
            'preferences' => [
                'App\Notifications\GroupDeleted' => ['email' => '0', 'web' => '0'],
            ],
        ])
        ->assertRedirect('/settings?section=notifications');

    // GroupDeleted only has email channel, so web should not be saved
    expect(NotificationPreference::where('user_id', $user->id)
        ->where('type', 'App\Notifications\GroupDeleted')
        ->where('channel', 'web')
        ->count())->toBe(0);

    expect(NotificationPreference::where('user_id', $user->id)
        ->where('type', 'App\Notifications\GroupDeleted')
        ->where('channel', 'email')
        ->count())->toBe(1);
});

it('requires authentication to view notification preferences', function () {
    $this->get('/settings?section=notifications')
        ->assertRedirect('/login');
});

it('requires authentication to update notification preferences', function () {
    $this->put('/settings/notifications', [
        'preferences' => [
            'App\Notifications\WelcomeToGroup' => ['email' => '1'],
        ],
    ])->assertRedirect('/login');
});

it('preferences affect notification delivery via NotificationService', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $rsvp = Rsvp::factory()->create(['user_id' => $user->id, 'event_id' => $event->id]);

    // Disable email for PromotedFromWaitlist
    NotificationPreference::create([
        'user_id' => $user->id,
        'type' => PromotedFromWaitlist::class,
        'channel' => 'email',
        'enabled' => false,
    ]);

    NotificationFacade::fake();

    $notification = new PromotedFromWaitlist(event: $event, rsvp: $rsvp);

    $service = app(NotificationService::class);
    $service->dispatch($user, $notification);

    // Should only send via database (web), not mail
    NotificationFacade::assertSentTo($user, PromotedFromWaitlist::class, function ($n, $channels) {
        return in_array('database', $channels) && ! in_array('mail', $channels);
    });
});

it('delivers on all default channels when no preference is set', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $rsvp = Rsvp::factory()->create(['user_id' => $user->id, 'event_id' => $event->id]);

    NotificationFacade::fake();

    $notification = new PromotedFromWaitlist(event: $event, rsvp: $rsvp);

    $service = app(NotificationService::class);
    $service->dispatch($user, $notification);

    // NotificationService sends non-email and email in separate notifyNow calls
    // Verify database channel was sent
    NotificationFacade::assertSentTo($user, PromotedFromWaitlist::class, function ($n, $channels) {
        return in_array('database', $channels);
    });

    // Verify mail channel was sent
    NotificationFacade::assertSentTo($user, PromotedFromWaitlist::class, function ($n, $channels) {
        return in_array('mail', $channels);
    });
});

it('validates preferences field is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/notifications', [])
        ->assertSessionHasErrors('preferences');
});
