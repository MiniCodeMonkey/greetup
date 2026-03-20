<?php

use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Tags\Tag;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

it('renders hero section with colored headline', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Find your')
        ->assertSee('people.')
        ->assertSee('Do the')
        ->assertSee('thing.')
        ->assertSee('Keep')
        ->assertSee('showing up.');
});

it('renders get started and explore events CTA buttons', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Get started')
        ->assertSee('Explore events');
});

it('renders stat cards with platform stats', function () {
    Group::factory()->count(3)->create();
    User::factory()->count(2)->create();

    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Groups')
        ->assertSee('Events')
        ->assertSee('Members');
});

it('renders popular interests when tags exist', function () {
    Tag::findOrCreate('Photography', 'interest');
    Tag::findOrCreate('Hiking', 'interest');

    $this->get('/')
        ->assertOk()
        ->assertSee('Popular interests')
        ->assertSee('Photography')
        ->assertSee('Hiking');
});

it('hides popular interests section when no tags exist', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('Popular interests');
});

it('renders upcoming events section when published events exist', function () {
    $group = Group::factory()->create();
    Event::factory()->published()->create([
        'group_id' => $group->id,
        'name' => 'Laravel Meetup Night',
        'starts_at' => now()->addDays(3),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Upcoming events')
        ->assertSee('Laravel Meetup Night');
});

it('hides upcoming events section when no upcoming events exist', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('Upcoming events');
});

it('does not show draft events on homepage', function () {
    $group = Group::factory()->create();
    Event::factory()->draft()->create([
        'group_id' => $group->id,
        'name' => 'Secret Draft Event',
        'starts_at' => now()->addDays(3),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('Secret Draft Event');
});

it('does not show past events on homepage', function () {
    $group = Group::factory()->create();
    Event::factory()->past()->create([
        'group_id' => $group->id,
        'name' => 'Old Past Event',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('Old Past Event');
});

it('redirects authenticated users to dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect('/dashboard');
});

it('limits upcoming events to six', function () {
    $group = Group::factory()->create();

    Event::factory()->published()->count(8)->create([
        'group_id' => $group->id,
        'starts_at' => now()->addDays(3),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Upcoming events')
        ->assertSee('View all');
});
