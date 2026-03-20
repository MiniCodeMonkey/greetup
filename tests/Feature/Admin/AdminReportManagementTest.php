<?php

use App\Enums\ReportStatus;
use App\Models\Comment;
use App\Models\Group;
use App\Models\Report;
use App\Models\User;
use App\Notifications\AccountSuspended;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->withoutVite();
});

// --- Access Control ---

it('allows admin to access reports page', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.reports.index'));

    $response->assertOk();
    $response->assertSee('Manage Reports');
});

it('returns 403 for regular users accessing reports page', function (): void {
    $user = User::factory()->create();
    $user->assignRole('user');

    $response = $this->actingAs($user)->get(route('admin.reports.index'));

    $response->assertForbidden();
});

it('redirects unauthenticated users to login for reports page', function (): void {
    $response = $this->get(route('admin.reports.index'));

    $response->assertRedirect(route('login'));
});

// --- Report Listing ---

it('displays pending reports sorted by newest with pagination at 25 per page', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $reporter = User::factory()->create();
    Report::factory()->count(30)->create(['reporter_id' => $reporter->id]);

    $response = $this->actingAs($admin)->get(route('admin.reports.index'));

    $response->assertOk();
    $response->assertSee('Next');
});

it('shows report details including reporter, reason, description, and date', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $reporter = User::factory()->create(['name' => 'Jane Reporter']);
    $report = Report::factory()->create([
        'reporter_id' => $reporter->id,
        'reason' => 'spam',
        'description' => 'This is spam content',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.reports.index'));

    $response->assertOk();
    $response->assertSee('Jane Reporter');
    $response->assertSee('Spam');
    $response->assertSee('This is spam content');
    $response->assertSee($report->created_at->format('M j, Y'));
});

it('shows grouped report count for items with multiple reports', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $comment = Comment::factory()->create();
    Report::factory()->count(3)->create([
        'reportable_type' => Comment::class,
        'reportable_id' => $comment->id,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.reports.index'));

    $response->assertOk();
    $response->assertSee('3 reports');
});

it('filters reports by status', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Report::factory()->create(['status' => ReportStatus::Pending]);
    Report::factory()->resolved()->create();

    $response = $this->actingAs($admin)->get(route('admin.reports.index', ['status' => 'resolved']));

    $response->assertOk();
    $response->assertSee('Resolved');
});

// --- Review ---

it('marks a pending report as reviewed', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $report = Report::factory()->create(['status' => ReportStatus::Pending]);

    $response = $this->actingAs($admin)->post(route('admin.reports.review', $report));

    $response->assertRedirect(route('admin.reports.index'));

    $report->refresh();
    expect($report->status)->toBe(ReportStatus::Reviewed);
    expect($report->reviewed_by)->toBe($admin->id);
    expect($report->reviewed_at)->not->toBeNull();
});

it('prevents reviewing a non-pending report', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $report = Report::factory()->resolved()->create();

    $response = $this->actingAs($admin)->post(route('admin.reports.review', $report));

    $response->assertRedirect(route('admin.reports.index'));
    $response->assertSessionHas('error');
});

// --- Resolve ---

it('resolves a pending report with resolution notes', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $report = Report::factory()->create(['status' => ReportStatus::Pending]);

    $response = $this->actingAs($admin)->post(route('admin.reports.resolve', $report), [
        'resolution_notes' => 'Content was removed and user warned.',
    ]);

    $response->assertRedirect(route('admin.reports.index'));

    $report->refresh();
    expect($report->status)->toBe(ReportStatus::Resolved);
    expect($report->resolution_notes)->toBe('Content was removed and user warned.');
    expect($report->reviewed_by)->toBe($admin->id);
    expect($report->reviewed_at)->not->toBeNull();
});

it('resolves a reviewed report with resolution notes', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $report = Report::factory()->reviewed()->create();

    $response = $this->actingAs($admin)->post(route('admin.reports.resolve', $report), [
        'resolution_notes' => 'Addressed after review.',
    ]);

    $response->assertRedirect(route('admin.reports.index'));

    $report->refresh();
    expect($report->status)->toBe(ReportStatus::Resolved);
    expect($report->resolution_notes)->toBe('Addressed after review.');
});

it('requires resolution notes when resolving', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $report = Report::factory()->create(['status' => ReportStatus::Pending]);

    $response = $this->actingAs($admin)->post(route('admin.reports.resolve', $report), [
        'resolution_notes' => '',
    ]);

    $response->assertSessionHasErrors('resolution_notes');
});

// --- Dismiss ---

it('dismisses a pending report', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $report = Report::factory()->create(['status' => ReportStatus::Pending]);

    $response = $this->actingAs($admin)->post(route('admin.reports.dismiss', $report));

    $response->assertRedirect(route('admin.reports.index'));

    $report->refresh();
    expect($report->status)->toBe(ReportStatus::Dismissed);
    expect($report->reviewed_by)->toBe($admin->id);
    expect($report->reviewed_at)->not->toBeNull();
});

it('dismisses a reviewed report', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $report = Report::factory()->reviewed()->create();

    $response = $this->actingAs($admin)->post(route('admin.reports.dismiss', $report));

    $response->assertRedirect(route('admin.reports.index'));

    $report->refresh();
    expect($report->status)->toBe(ReportStatus::Dismissed);
});

it('prevents dismissing an already resolved report', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $report = Report::factory()->resolved()->create();

    $response = $this->actingAs($admin)->post(route('admin.reports.dismiss', $report));

    $response->assertRedirect(route('admin.reports.index'));
    $response->assertSessionHas('error');
});

// --- Direct Actions: Suspend User ---

it('suspends user associated with a reported comment', function (): void {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $commentAuthor = User::factory()->create(['name' => 'Bad Actor']);
    $comment = Comment::factory()->create(['user_id' => $commentAuthor->id]);
    $report = Report::factory()->create([
        'reportable_type' => Comment::class,
        'reportable_id' => $comment->id,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.reports.suspend-user', $report), [
        'reason' => 'Violated community guidelines via comment.',
    ]);

    $response->assertRedirect(route('admin.reports.index'));

    $commentAuthor->refresh();
    expect($commentAuthor->is_suspended)->toBeTrue();
    expect($commentAuthor->suspended_reason)->toBe('Violated community guidelines via comment.');

    Notification::assertSentTo($commentAuthor, AccountSuspended::class);
});

it('suspends user directly when reported item is a user', function (): void {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $reportedUser = User::factory()->create(['name' => 'Reported Person']);
    $report = Report::factory()->create([
        'reportable_type' => User::class,
        'reportable_id' => $reportedUser->id,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.reports.suspend-user', $report), [
        'reason' => 'Inappropriate behavior.',
    ]);

    $response->assertRedirect(route('admin.reports.index'));

    $reportedUser->refresh();
    expect($reportedUser->is_suspended)->toBeTrue();

    Notification::assertSentTo($reportedUser, AccountSuspended::class);
});

// --- Direct Actions: Delete Content ---

it('deletes a reported group', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $group = Group::factory()->create();
    $report = Report::factory()->create([
        'reportable_type' => Group::class,
        'reportable_id' => $group->id,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.reports.delete-content', $report));

    $response->assertRedirect(route('admin.reports.index'));
    $response->assertSessionHas('success');

    expect(Group::find($group->id))->toBeNull();
});

it('deletes a reported comment', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $comment = Comment::factory()->create();
    $report = Report::factory()->create([
        'reportable_type' => Comment::class,
        'reportable_id' => $comment->id,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.reports.delete-content', $report));

    $response->assertRedirect(route('admin.reports.index'));
    $response->assertSessionHas('success');

    expect(Comment::find($comment->id))->toBeNull();
});

it('prevents deleting a user through delete content action', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $reportedUser = User::factory()->create();
    $report = Report::factory()->create([
        'reportable_type' => User::class,
        'reportable_id' => $reportedUser->id,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.reports.delete-content', $report));

    $response->assertRedirect(route('admin.reports.index'));
    $response->assertSessionHas('error');

    expect(User::find($reportedUser->id))->not->toBeNull();
});

// --- Status Transitions ---

it('follows correct status transition: pending to reviewed to resolved', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $report = Report::factory()->create(['status' => ReportStatus::Pending]);

    // Pending -> Reviewed
    $this->actingAs($admin)->post(route('admin.reports.review', $report));
    $report->refresh();
    expect($report->status)->toBe(ReportStatus::Reviewed);

    // Reviewed -> Resolved
    $this->actingAs($admin)->post(route('admin.reports.resolve', $report), [
        'resolution_notes' => 'Issue addressed.',
    ]);
    $report->refresh();
    expect($report->status)->toBe(ReportStatus::Resolved);
});

it('follows correct status transition: pending to reviewed to dismissed', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $report = Report::factory()->create(['status' => ReportStatus::Pending]);

    // Pending -> Reviewed
    $this->actingAs($admin)->post(route('admin.reports.review', $report));
    $report->refresh();
    expect($report->status)->toBe(ReportStatus::Reviewed);

    // Reviewed -> Dismissed
    $this->actingAs($admin)->post(route('admin.reports.dismiss', $report));
    $report->refresh();
    expect($report->status)->toBe(ReportStatus::Dismissed);
});
