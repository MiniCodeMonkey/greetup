<?php

use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Jobs\GeocodeLocation;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

it('displays the group creation form', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('groups.create'))
        ->assertStatus(200)
        ->assertSee('Create a Group')
        ->assertSee('Group Name');
});

it('requires authentication to view the creation form', function (): void {
    $this->get(route('groups.create'))
        ->assertRedirect(route('login'));
});

it('requires authentication to store a group', function (): void {
    $this->post(route('groups.store'), [
        'name' => 'Test Group',
        'visibility' => 'public',
    ])->assertRedirect(route('login'));
});

it('creates a group with all fields on happy path', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('groups.store'), [
            'name' => 'Copenhagen Laravel Meetup',
            'description' => '## Welcome\n\nA group for **Laravel** enthusiasts.',
            'location' => 'Copenhagen, Denmark',
            'visibility' => 'public',
            'requires_approval' => true,
            'max_members' => 100,
            'welcome_message' => 'Welcome to the group!',
            'topics' => ['Laravel', 'PHP', 'Web Development'],
            'membership_questions' => [
                ['question' => 'Why do you want to join?', 'is_required' => true],
                ['question' => 'What is your experience level?', 'is_required' => false],
            ],
        ]);

    $group = Group::where('name', 'Copenhagen Laravel Meetup')->first();
    expect($group)->not->toBeNull();

    $response->assertRedirect(route('groups.show', $group));

    // Verify group attributes
    expect($group->description)->toBe('## Welcome\n\nA group for **Laravel** enthusiasts.');
    expect($group->description_html)->toContain('Welcome');
    expect($group->location)->toBe('Copenhagen, Denmark');
    expect($group->visibility)->toBe(GroupVisibility::Public);
    expect($group->requires_approval)->toBeTrue();
    expect($group->max_members)->toBe(100);
    expect($group->welcome_message)->toBe('Welcome to the group!');
    expect($group->organizer_id)->toBe($user->id);

    // Verify slug auto-generated
    expect($group->slug)->toBe('copenhagen-laravel-meetup');

    // Verify creator is organizer member
    $membership = $group->members()->where('user_id', $user->id)->first();
    expect($membership)->not->toBeNull();
    expect($membership->pivot->role)->toBe(GroupRole::Organizer);
    expect($membership->pivot->joined_at)->not->toBeNull();

    // Verify topics
    $topics = $group->tagsWithType('topic')->pluck('name')->toArray();
    expect($topics)->toContain('Laravel');
    expect($topics)->toContain('PHP');
    expect($topics)->toContain('Web Development');

    // Verify membership questions
    $questions = $group->membershipQuestions()->orderBy('sort_order')->get();
    expect($questions)->toHaveCount(2);
    expect($questions[0]->question)->toBe('Why do you want to join?');
    expect($questions[0]->is_required)->toBeTrue();
    expect($questions[0]->sort_order)->toBe(0);
    expect($questions[1]->question)->toBe('What is your experience level?');
    expect($questions[1]->is_required)->toBeFalse();
    expect($questions[1]->sort_order)->toBe(1);

    // Verify geocoding job dispatched
    Queue::assertPushed(GeocodeLocation::class, function (GeocodeLocation $job) use ($group) {
        return $job->model->is($group);
    });
});

it('creates a group with minimal required fields', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('groups.store'), [
            'name' => 'Minimal Group',
            'visibility' => 'public',
        ]);

    $group = Group::where('name', 'Minimal Group')->first();
    expect($group)->not->toBeNull();
    $response->assertRedirect(route('groups.show', $group));

    expect($group->slug)->toBe('minimal-group');
    expect($group->description)->toBeNull();
    expect($group->description_html)->toBeNull();
    expect($group->location)->toBeNull();
    expect($group->requires_approval)->toBeFalse();
    expect($group->max_members)->toBeNull();
});

it('handles slug collisions automatically', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    // Create first group
    $this->actingAs($user)
        ->post(route('groups.store'), [
            'name' => 'Unique Group',
            'visibility' => 'public',
        ]);

    // Create second group with same name
    $this->actingAs($user)
        ->post(route('groups.store'), [
            'name' => 'Unique Group',
            'visibility' => 'public',
        ]);

    $groups = Group::where('name', 'Unique Group')->get();
    expect($groups)->toHaveCount(2);

    $slugs = $groups->pluck('slug')->toArray();
    expect($slugs[0])->not->toBe($slugs[1]);
    expect($slugs)->toContain('unique-group');
});

it('validates required name field', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('groups.store'), [
            'visibility' => 'public',
        ])
        ->assertSessionHasErrors('name');
});

it('validates required visibility field', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('groups.store'), [
            'name' => 'Test Group',
        ])
        ->assertSessionHasErrors('visibility');
});

it('validates name max length', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('groups.store'), [
            'name' => str_repeat('a', 256),
            'visibility' => 'public',
        ])
        ->assertSessionHasErrors('name');
});

it('validates visibility enum values', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('groups.store'), [
            'name' => 'Test Group',
            'visibility' => 'invalid',
        ])
        ->assertSessionHasErrors('visibility');
});

it('validates max_members minimum value', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('groups.store'), [
            'name' => 'Test Group',
            'visibility' => 'public',
            'max_members' => 1,
        ])
        ->assertSessionHasErrors('max_members');
});

it('validates description max length', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('groups.store'), [
            'name' => 'Test Group',
            'visibility' => 'public',
            'description' => str_repeat('a', 10001),
        ])
        ->assertSessionHasErrors('description');
});

it('prevents suspended users from creating a group', function (): void {
    $user = User::factory()->create(['is_suspended' => true]);

    $this->actingAs($user)
        ->post(route('groups.store'), [
            'name' => 'Test Group',
            'visibility' => 'public',
        ])
        ->assertRedirect(route('suspended'));
});

it('prevents unverified users from creating a group', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('groups.store'), [
            'name' => 'Test Group',
            'visibility' => 'public',
        ])
        ->assertForbidden();
});

it('does not dispatch geocoding job when location is not provided', function (): void {
    $user = User::factory()->create();

    Queue::fake();

    $this->actingAs($user)
        ->post(route('groups.store'), [
            'name' => 'No Location Group',
            'visibility' => 'public',
        ]);

    Queue::assertNotPushed(GeocodeLocation::class);
});

it('renders description markdown to html on save', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('groups.store'), [
            'name' => 'Markdown Group',
            'visibility' => 'public',
            'description' => '**Bold text** and *italic text*',
        ]);

    $group = Group::where('name', 'Markdown Group')->first();
    expect($group->description_html)->toContain('<strong>Bold text</strong>');
    expect($group->description_html)->toContain('<em>italic text</em>');
});
