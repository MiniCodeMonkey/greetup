<?php

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\GroupVisibility;
use App\Enums\ProfileVisibility;
use App\Models\Event;
use App\Models\Group;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

// --- Homepage ---

it('renders homepage with correct title format for guests', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<title>Greetup — Find your people</title>', false);
});

it('renders homepage with site_description setting as meta description', function () {
    Setting::create(['key' => 'site_description', 'value' => 'A custom community platform.']);
    Setting::clearCache();

    $this->get('/')
        ->assertOk()
        ->assertSee('<meta name="description" content="A custom community platform.">', false);
});

it('renders homepage meta description fallback when site_description is empty', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('A free, open source community events platform.', false);
});

it('renders homepage with custom site_name from settings', function () {
    Setting::create(['key' => 'site_name', 'value' => 'MyMeetups']);
    Setting::clearCache();

    $this->get('/')
        ->assertOk()
        ->assertSee('<title>MyMeetups — Find your people</title>', false);
});

it('redirects authenticated users from homepage to dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect('/dashboard');
});

// --- Explore page ---

it('renders explore page with correct title format', function () {
    $this->get('/explore')
        ->assertOk()
        ->assertSee('<title>Explore Events — Greetup</title>', false);
});

// --- Group search page ---

it('renders group search page with correct title format', function () {
    $this->get('/groups')
        ->assertOk()
        ->assertSee('<title>Browse Groups — Greetup</title>', false);
});

// --- Search page ---

it('renders search page with query in title', function () {
    $this->get('/search?query=hiking')
        ->assertOk()
        ->assertSee('Search: &quot;hiking&quot; — Greetup', false);
});

// --- Group page ---

it('renders group page with correct title and meta description', function () {
    $organizer = User::factory()->create();
    $group = Group::factory()->create([
        'name' => 'Hiking Enthusiasts',
        'description' => 'A group for people who love hiking in the mountains and exploring nature trails together.',
        'visibility' => GroupVisibility::Public,
        'organizer_id' => $organizer->id,
    ]);

    $this->get("/groups/{$group->slug}")
        ->assertOk()
        ->assertSee('<title>Hiking Enthusiasts — Greetup</title>', false)
        ->assertSee('<meta name="description" content="A group for people who love hiking', false);
});

// --- Event page ---

it('renders event page with correct title format', function () {
    $organizer = User::factory()->create();
    $group = Group::factory()->create([
        'name' => 'Hiking Club',
        'visibility' => GroupVisibility::Public,
        'organizer_id' => $organizer->id,
    ]);
    $event = Event::factory()->create([
        'name' => 'Mountain Trek',
        'group_id' => $group->id,
        'status' => EventStatus::Published,
        'event_type' => EventType::InPerson,
        'starts_at' => now()->addDays(7),
    ]);

    $this->get("/groups/{$group->slug}/events/{$event->slug}")
        ->assertOk()
        ->assertSee('Mountain Trek · Hiking Club — Greetup', false);
});

// --- User profile ---

it('renders user profile with correct title and bio description', function () {
    $user = User::factory()->create([
        'name' => 'Jane Doe',
        'bio' => 'I am a software engineer who loves hiking.',
        'profile_visibility' => ProfileVisibility::Public,
    ]);

    $this->get("/members/{$user->id}")
        ->assertOk()
        ->assertSee('<title>Jane Doe — Greetup</title>', false)
        ->assertSee('<meta name="description" content="I am a software engineer who loves hiking.">', false);
});

it('renders user profile with fallback description when no bio', function () {
    $user = User::factory()->create([
        'name' => 'Jane Doe',
        'bio' => null,
        'profile_visibility' => ProfileVisibility::Public,
    ]);

    $this->get("/members/{$user->id}")
        ->assertOk()
        ->assertSee('<meta name="description" content="Jane Doe is a member of Greetup.">', false);
});

// --- Dashboard ---

it('renders dashboard with correct title format', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('<title>Dashboard — Greetup</title>', false);
});

// --- Admin pages ---

it('renders admin dashboard with correct title format', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('<title>Admin: Dashboard — Greetup</title>', false);
});

// --- Error pages ---

it('renders error pages with correct title format', function (int $code) {
    $this->get("/non-existent-route-for-{$code}")
        ->assertStatus(404)
        ->assertSee('404 — Greetup', false);
})->with([404]);

// --- Canonical URLs ---

it('renders canonical url on public pages', function () {
    $this->get('/explore')
        ->assertOk()
        ->assertSee('rel="canonical"', false);
});

it('renders canonical url without query parameters', function () {
    $response = $this->get('/groups');

    $response->assertOk()
        ->assertSee('rel="canonical"', false);

    // Ensure the canonical does not contain query parameters
    $content = $response->getContent();
    preg_match('/rel="canonical" href="([^"]+)"/', $content, $matches);
    expect($matches[1] ?? '')->not->toContain('?');
});

// --- OG tags ---

it('renders og default image when no specific image provided', function () {
    $this->get('/explore')
        ->assertOk()
        ->assertSee('og-default.png', false);
});

it('renders og:site_name from platform settings', function () {
    Setting::create(['key' => 'site_name', 'value' => 'MyMeetups']);
    Setting::clearCache();

    $this->get('/explore')
        ->assertOk()
        ->assertSee('<meta property="og:site_name" content="MyMeetups">', false);
});
