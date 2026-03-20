<?php

use App\Models\Comment;
use App\Models\Event;
use App\Models\Group;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;

uses(DatabaseMigrations::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('admin can see dashboard with stats', function () {
    $admin = User::factory()->admin()->create(['name' => 'Admin User']);

    // Create some data for stats
    User::factory()->count(3)->create();
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);

    $this->browse(function (Browser $browser) use ($admin) {
        $browser->loginAs($admin)
            ->visit('/admin')
            ->assertSee('Admin Dashboard')
            ->assertPresent('[data-testid="stat-total-users"]')
            ->assertPresent('[data-testid="stat-total-groups"]')
            ->assertPresent('[data-testid="stat-total-events"]');
    });
});

test('admin can review a report', function () {
    $admin = User::factory()->admin()->create();

    // Create a report
    $reporter = User::factory()->create();
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
    ]);
    $event->hosts()->attach($organizer->id);
    $comment = Comment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $reporter->id,
    ]);
    $report = Report::factory()->create([
        'reporter_id' => $reporter->id,
        'reportable_type' => Comment::class,
        'reportable_id' => $comment->id,
    ]);

    $this->browse(function (Browser $browser) use ($admin) {
        $browser->loginAs($admin)
            ->visit('/admin/reports')
            ->assertSee('Manage Reports')
            ->assertSee('Pending')
            ->press('Review')
            ->waitForText('Reviewed');
    });
});

test('admin can resolve a report', function () {
    $admin = User::factory()->admin()->create();

    $reporter = User::factory()->create();
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
    ]);
    $event->hosts()->attach($organizer->id);
    $comment = Comment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $reporter->id,
    ]);
    Report::factory()->create([
        'reporter_id' => $reporter->id,
        'reportable_type' => Comment::class,
        'reportable_id' => $comment->id,
    ]);

    $this->browse(function (Browser $browser) use ($admin) {
        $browser->loginAs($admin)
            ->visit('/admin/reports')
            ->assertSee('Manage Reports')
            ->click('button.text-green-600')  // Resolve button
            ->waitFor('textarea[name="resolution_notes"]')
            ->type('textarea[name="resolution_notes"]', 'Resolved: Content was appropriate.')
            ->press('Resolve')
            ->pause(1000)
            ->assertDontSee('Pending');
    });
});
