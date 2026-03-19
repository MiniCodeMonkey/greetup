<?php

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Comment;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('can be created using the factory', function (): void {
    $report = Report::factory()->create();

    expect($report)->toBeInstanceOf(Report::class)
        ->and($report->exists)->toBeTrue()
        ->and($report->reason)->toBeInstanceOf(ReportReason::class)
        ->and($report->status)->toBe(ReportStatus::Pending);
});

it('has reporter belongsTo relationship', function (): void {
    $report = Report::factory()->create();

    expect($report->reporter())->toBeInstanceOf(BelongsTo::class)
        ->and($report->reporter)->toBeInstanceOf(User::class);
});

it('has reportable morphTo relationship', function (): void {
    $comment = Comment::factory()->create();
    $report = Report::factory()->create([
        'reportable_type' => Comment::class,
        'reportable_id' => $comment->id,
    ]);

    expect($report->reportable())->toBeInstanceOf(MorphTo::class)
        ->and($report->reportable)->toBeInstanceOf(Comment::class)
        ->and($report->reportable->id)->toBe($comment->id);
});

it('has reviewer belongsTo relationship', function (): void {
    $report = Report::factory()->reviewed()->create();

    expect($report->reviewer())->toBeInstanceOf(BelongsTo::class)
        ->and($report->reviewer)->toBeInstanceOf(User::class);
});

it('has pending scope', function (): void {
    Report::factory()->create(['status' => ReportStatus::Pending]);
    Report::factory()->resolved()->create();

    expect(Report::pending()->count())->toBe(1);
});

it('has reviewed scope', function (): void {
    Report::factory()->reviewed()->create();
    Report::factory()->create(['status' => ReportStatus::Pending]);

    expect(Report::reviewed()->count())->toBe(1);
});

it('has resolved scope', function (): void {
    Report::factory()->resolved()->create();
    Report::factory()->create(['status' => ReportStatus::Pending]);

    expect(Report::resolved()->count())->toBe(1);
});

it('has dismissed scope', function (): void {
    Report::factory()->dismissed()->create();
    Report::factory()->create(['status' => ReportStatus::Pending]);

    expect(Report::dismissed()->count())->toBe(1);
});

it('casts reason to ReportReason enum', function (): void {
    $report = Report::factory()->create(['reason' => ReportReason::Spam]);

    expect($report->reason)->toBe(ReportReason::Spam);
});

it('casts status to ReportStatus enum', function (): void {
    $report = Report::factory()->create(['status' => ReportStatus::Resolved]);

    expect($report->status)->toBe(ReportStatus::Resolved);
});

it('casts reviewed_at to datetime', function (): void {
    $report = Report::factory()->reviewed()->create();

    expect($report->reviewed_at)->toBeInstanceOf(Carbon::class);
});

it('defaults status to pending', function (): void {
    $report = Report::factory()->create();

    expect($report->status)->toBe(ReportStatus::Pending);
});
