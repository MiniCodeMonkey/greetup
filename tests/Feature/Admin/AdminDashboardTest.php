<?php

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Event;
use App\Models\Group;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->withoutVite();
});

it('allows admin users to access the admin dashboard', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Admin Dashboard');
});

it('returns 403 for regular users', function (): void {
    $user = User::factory()->create();
    $user->assignRole('user');

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertForbidden();
});

it('redirects unauthenticated users to login', function (): void {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('login'));
});

it('displays accurate platform statistics', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Create test data
    User::factory()->count(3)->create();
    $group = Group::factory()->create();
    Event::factory()->count(2)->for($group)->create();

    // Create an event from last month (should not count in "this month")
    Event::factory()->for($group)->create([
        'created_at' => now()->subMonth(),
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();

    // Verify all stat labels are present
    $response->assertSee('Total Users');
    $response->assertSee('Total Groups');
    $response->assertSee('Total Events');
    $response->assertSee('Events This Month');
    $response->assertSee('New Users This Week');

    // Verify actual stat values
    $totalUsers = User::count();
    $totalGroups = Group::count();
    $totalEvents = Event::count();
    $eventsThisMonth = Event::where('created_at', '>=', now()->startOfMonth())->count();

    $response->assertSeeInOrder(['Total Users', (string) $totalUsers]);
    $response->assertSeeInOrder(['Total Groups', (string) $totalGroups]);
    $response->assertSeeInOrder(['Total Events', (string) $totalEvents]);
    $response->assertSeeInOrder(['Events This Month', (string) $eventsThisMonth]);
});

it('displays the correct SEO title', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Admin: Dashboard — '.config('app.name', 'Greetup'), false);
});

it('shows recent pending reports', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $reporter = User::factory()->create();
    $reportedUser = User::factory()->create();

    Report::create([
        'reporter_id' => $reporter->id,
        'reportable_type' => User::class,
        'reportable_id' => $reportedUser->id,
        'reason' => ReportReason::Spam,
        'status' => ReportStatus::Pending,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Recent Reports Needing Review');
    $response->assertSee($reporter->name);
});

it('shows recently created groups', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Group::factory()->create(['name' => 'Test Admin Group']);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Recently Created Groups');
    $response->assertSee('Test Admin Group');
});

it('shows quick links for admin management', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Manage Users');
    $response->assertSee('Manage Groups');
    $response->assertSee('Manage Reports');
    $response->assertSee('Settings');
    $response->assertSee('Manage Interests');
});
