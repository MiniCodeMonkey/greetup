<?php

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Comment;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\Event;
use App\Models\EventChatMessage;
use App\Models\Group;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->withoutVite();
});

it('creates a report for a user profile', function (): void {
    Notification::fake();

    $reporter = User::factory()->create();
    $reported = User::factory()->create();

    $response = $this->actingAs($reporter)
        ->post(route('reports.store'), [
            'reportable_type' => 'user',
            'reportable_id' => $reported->id,
            'reason' => ReportReason::Harassment->value,
            'description' => 'This user is harassing me.',
        ]);

    $response->assertSessionHas('status');
    $response->assertSessionDoesntHaveErrors();

    $report = Report::first();
    expect($report)->not->toBeNull();
    expect($report->reporter_id)->toBe($reporter->id);
    expect($report->reportable_type)->toBe(User::class);
    expect($report->reportable_id)->toBe($reported->id);
    expect($report->reason)->toBe(ReportReason::Harassment);
    expect($report->description)->toBe('This user is harassing me.');
    expect($report->status)->toBe(ReportStatus::Pending);
});

it('creates a report for a group', function (): void {
    Notification::fake();

    $reporter = User::factory()->create();
    $group = Group::factory()->create();

    $this->actingAs($reporter)
        ->post(route('reports.store'), [
            'reportable_type' => 'group',
            'reportable_id' => $group->id,
            'reason' => ReportReason::Spam->value,
        ]);

    $report = Report::first();
    expect($report)->not->toBeNull();
    expect($report->reportable_type)->toBe(Group::class);
    expect($report->reportable_id)->toBe($group->id);
});

it('creates a report for an event', function (): void {
    Notification::fake();

    $reporter = User::factory()->create();
    $event = Event::factory()->create();

    $this->actingAs($reporter)
        ->post(route('reports.store'), [
            'reportable_type' => 'event',
            'reportable_id' => $event->id,
            'reason' => ReportReason::Misleading->value,
        ]);

    $report = Report::first();
    expect($report)->not->toBeNull();
    expect($report->reportable_type)->toBe(Event::class);
});

it('creates a report for an event comment', function (): void {
    Notification::fake();

    $reporter = User::factory()->create();
    $comment = Comment::factory()->create();

    $this->actingAs($reporter)
        ->post(route('reports.store'), [
            'reportable_type' => 'comment',
            'reportable_id' => $comment->id,
            'reason' => ReportReason::InappropriateContent->value,
        ]);

    $report = Report::first();
    expect($report)->not->toBeNull();
    expect($report->reportable_type)->toBe(Comment::class);
});

it('creates a report for a discussion', function (): void {
    Notification::fake();

    $reporter = User::factory()->create();
    $discussion = Discussion::factory()->create();

    $this->actingAs($reporter)
        ->post(route('reports.store'), [
            'reportable_type' => 'discussion',
            'reportable_id' => $discussion->id,
            'reason' => ReportReason::HateSpeech->value,
        ]);

    $report = Report::first();
    expect($report)->not->toBeNull();
    expect($report->reportable_type)->toBe(Discussion::class);
});

it('creates a report for a discussion reply', function (): void {
    Notification::fake();

    $reporter = User::factory()->create();
    $reply = DiscussionReply::factory()->create();

    $this->actingAs($reporter)
        ->post(route('reports.store'), [
            'reportable_type' => 'discussion_reply',
            'reportable_id' => $reply->id,
            'reason' => ReportReason::Impersonation->value,
        ]);

    $report = Report::first();
    expect($report)->not->toBeNull();
    expect($report->reportable_type)->toBe(DiscussionReply::class);
});

it('creates a report for a chat message', function (): void {
    Notification::fake();

    $reporter = User::factory()->create();
    $chatMessage = EventChatMessage::factory()->create();

    $this->actingAs($reporter)
        ->post(route('reports.store'), [
            'reportable_type' => 'chat_message',
            'reportable_id' => $chatMessage->id,
            'reason' => ReportReason::Other->value,
            'description' => 'Offensive language in chat.',
        ]);

    $report = Report::first();
    expect($report)->not->toBeNull();
    expect($report->reportable_type)->toBe(EventChatMessage::class);
});

it('rejects duplicate pending report from same reporter on same item', function (): void {
    Notification::fake();

    $reporter = User::factory()->create();
    $reported = User::factory()->create();

    // First report succeeds
    $this->actingAs($reporter)
        ->post(route('reports.store'), [
            'reportable_type' => 'user',
            'reportable_id' => $reported->id,
            'reason' => ReportReason::Spam->value,
        ]);

    expect(Report::count())->toBe(1);

    // Second report on same item is rejected
    $response = $this->actingAs($reporter)
        ->post(route('reports.store'), [
            'reportable_type' => 'user',
            'reportable_id' => $reported->id,
            'reason' => ReportReason::Harassment->value,
        ]);

    $response->assertSessionHasErrors('report');
    expect(Report::count())->toBe(1);
});

it('allows report after previous one was resolved', function (): void {
    Notification::fake();

    $reporter = User::factory()->create();
    $reported = User::factory()->create();

    // Create a resolved report
    Report::factory()->create([
        'reporter_id' => $reporter->id,
        'reportable_type' => User::class,
        'reportable_id' => $reported->id,
        'status' => ReportStatus::Resolved,
    ]);

    // New report on same item should succeed
    $this->actingAs($reporter)
        ->post(route('reports.store'), [
            'reportable_type' => 'user',
            'reportable_id' => $reported->id,
            'reason' => ReportReason::Spam->value,
        ]);

    expect(Report::pending()->count())->toBe(1);
});

it('sends ReportReceived notification to all platform admins', function (): void {
    Notification::fake();

    $reporter = User::factory()->create();
    $reported = User::factory()->create();

    $admin1 = User::factory()->create();
    $admin1->assignRole('admin');
    $admin2 = User::factory()->create();
    $admin2->assignRole('admin');

    $this->actingAs($reporter)
        ->post(route('reports.store'), [
            'reportable_type' => 'user',
            'reportable_id' => $reported->id,
            'reason' => ReportReason::Spam->value,
        ]);

    Notification::assertSentTo($admin1, ReportReceived::class);
    Notification::assertSentTo($admin2, ReportReceived::class);
    Notification::assertNotSentTo($reporter, ReportReceived::class);
});

it('sends ReportReceived notification via web and email channels', function (): void {
    Notification::fake();

    $reporter = User::factory()->create();
    $reported = User::factory()->create();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($reporter)
        ->post(route('reports.store'), [
            'reportable_type' => 'user',
            'reportable_id' => $reported->id,
            'reason' => ReportReason::Spam->value,
        ]);

    Notification::assertSentTo($admin, ReportReceived::class, function ($notification, $channels) {
        return in_array('mail', $channels) && in_array('database', $channels);
    });
});

it('requires authentication to submit a report', function (): void {
    $reported = User::factory()->create();

    $this->post(route('reports.store'), [
        'reportable_type' => 'user',
        'reportable_id' => $reported->id,
        'reason' => ReportReason::Spam->value,
    ])->assertRedirect(route('login'));
});

it('validates required fields', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('reports.store'), [])
        ->assertSessionHasErrors(['reportable_type', 'reportable_id', 'reason']);
});

it('validates reason is a valid enum value', function (): void {
    $user = User::factory()->create();
    $reported = User::factory()->create();

    $this->actingAs($user)
        ->post(route('reports.store'), [
            'reportable_type' => 'user',
            'reportable_id' => $reported->id,
            'reason' => 'invalid_reason',
        ])
        ->assertSessionHasErrors('reason');
});

it('validates reportable_type is a valid type', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('reports.store'), [
            'reportable_type' => 'invalid_type',
            'reportable_id' => 1,
            'reason' => ReportReason::Spam->value,
        ])
        ->assertSessionHasErrors('reportable_type');
});

it('rejects report for non-existent content', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('reports.store'), [
            'reportable_type' => 'user',
            'reportable_id' => 99999,
            'reason' => ReportReason::Spam->value,
        ]);

    $response->assertSessionHasErrors('reportable_id');
    expect(Report::count())->toBe(0);
});

it('allows description to be optional', function (): void {
    Notification::fake();

    $reporter = User::factory()->create();
    $reported = User::factory()->create();

    $this->actingAs($reporter)
        ->post(route('reports.store'), [
            'reportable_type' => 'user',
            'reportable_id' => $reported->id,
            'reason' => ReportReason::Spam->value,
        ]);

    $report = Report::first();
    expect($report)->not->toBeNull();
    expect($report->description)->toBeNull();
});
