<?php

use App\Enums\NotificationChannel;
use App\Enums\RsvpStatus;
use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\Discussion;
use App\Models\Event;
use App\Models\Group;
use App\Models\NotificationPreference;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

it('requires authentication to export data', function (): void {
    $this->get(route('settings.data-export'))
        ->assertRedirect(route('login'));
});

it('downloads a JSON file with expected data sections', function (): void {
    $user = User::factory()->create([
        'name' => 'Export User',
        'email' => 'export@example.com',
    ]);

    // Create group membership
    $group = Group::factory()->create();
    $group->members()->attach($user, ['role' => 'member', 'joined_at' => now()]);

    // Create RSVP
    $event = Event::factory()->create();
    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => RsvpStatus::Going,
        'guest_count' => 2,
        'checked_in' => false,
    ]);

    // Create discussion
    Discussion::factory()->create([
        'user_id' => $user->id,
        'group_id' => $group->id,
        'title' => 'Test Discussion',
        'body' => 'Discussion body',
    ]);

    // Create direct message
    $conversation = Conversation::factory()->create();
    DirectMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'body' => 'Hello there',
    ]);

    // Create notification preference
    NotificationPreference::create([
        'user_id' => $user->id,
        'type' => 'App\Notifications\NewEvent',
        'channel' => NotificationChannel::Email,
        'enabled' => false,
    ]);

    $response = $this->actingAs($user)
        ->get(route('settings.data-export'));

    $response->assertOk()
        ->assertDownload();

    $data = json_decode($response->streamedContent(), true);

    expect($data)->toHaveKeys(['profile', 'groups', 'rsvps', 'discussions', 'messages', 'notification_preferences'])
        ->and($data['profile']['name'])->toBe('Export User')
        ->and($data['profile']['email'])->toBe('export@example.com')
        ->and($data['groups'])->toHaveCount(1)
        ->and($data['groups'][0]['name'])->toBe($group->name)
        ->and($data['rsvps'])->toHaveCount(1)
        ->and($data['rsvps'][0]['status'])->toBe('going')
        ->and($data['rsvps'][0]['guest_count'])->toBe(2)
        ->and($data['discussions'])->toHaveCount(1)
        ->and($data['discussions'][0]['title'])->toBe('Test Discussion')
        ->and($data['messages'])->toHaveCount(1)
        ->and($data['messages'][0]['body'])->toBe('Hello there')
        ->and($data['notification_preferences'])->toHaveCount(1)
        ->and($data['notification_preferences'][0]['type'])->toBe('App\Notifications\NewEvent')
        ->and($data['notification_preferences'][0]['enabled'])->toBeFalse();
});
