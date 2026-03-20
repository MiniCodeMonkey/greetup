<?php

use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Jobs\GeocodeLocation;
use App\Models\Group;
use App\Models\GroupMembershipQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

it('rejects a regular member from accessing settings', function (): void {
    $group = Group::factory()->create();
    $member = User::factory()->create();

    $group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($member)
        ->get(route('groups.manage.settings', $group))
        ->assertForbidden();
});

it('rejects an event organizer from accessing settings', function (): void {
    $group = Group::factory()->create();
    $eventOrganizer = User::factory()->create();

    $group->members()->attach($eventOrganizer->id, [
        'role' => GroupRole::EventOrganizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($eventOrganizer)
        ->get(route('groups.manage.settings', $group))
        ->assertForbidden();
});

it('rejects an assistant organizer from accessing settings', function (): void {
    $group = Group::factory()->create();
    $assistant = User::factory()->create();

    $group->members()->attach($assistant->id, [
        'role' => GroupRole::AssistantOrganizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($assistant)
        ->get(route('groups.manage.settings', $group))
        ->assertForbidden();
});

it('allows a co-organizer to access settings', function (): void {
    $group = Group::factory()->create();
    $coOrganizer = User::factory()->create();

    $group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($coOrganizer)
        ->get(route('groups.manage.settings', $group))
        ->assertOk()
        ->assertSee('Group Settings');
});

it('allows the organizer to access settings', function (): void {
    $group = Group::factory()->create();
    $organizer = $group->organizer;

    $group->members()->attach($organizer->id, [
        'role' => GroupRole::Organizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($organizer)
        ->get(route('groups.manage.settings', $group))
        ->assertOk()
        ->assertSee('Group Settings');
});

it('requires authentication to access settings', function (): void {
    $group = Group::factory()->create();

    $this->get(route('groups.manage.settings', $group))
        ->assertRedirect(route('login'));
});

it('updates group settings', function (): void {
    Queue::fake();

    $group = Group::factory()->create([
        'name' => 'Original Name',
        'description' => 'Original description',
        'visibility' => GroupVisibility::Public,
    ]);
    $coOrganizer = User::factory()->create();

    $group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    $response = $this->actingAs($coOrganizer)
        ->put(route('groups.manage.settings.update', $group), [
            'name' => 'Updated Name',
            'slug' => $group->slug,
            'description' => '**Bold description**',
            'location' => 'Berlin, Germany',
            'visibility' => 'private',
            'requires_approval' => true,
            'max_members' => 50,
            'welcome_message' => 'Welcome aboard!',
        ]);

    $group->refresh();

    expect($group->name)->toBe('Updated Name');
    expect($group->description)->toBe('**Bold description**');
    expect($group->description_html)->toContain('<strong>Bold description</strong>');
    expect($group->location)->toBe('Berlin, Germany');
    expect($group->visibility)->toBe(GroupVisibility::Private);
    expect($group->requires_approval)->toBeTrue();
    expect($group->max_members)->toBe(50);
    expect($group->welcome_message)->toBe('Welcome aboard!');

    $response->assertRedirect(route('groups.manage.settings', $group));
});

it('allows changing the group slug', function (): void {
    Queue::fake();

    $group = Group::factory()->create(['slug' => 'original-slug']);
    $coOrganizer = User::factory()->create();

    $group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($coOrganizer)
        ->put(route('groups.manage.settings.update', $group), [
            'name' => $group->name,
            'slug' => 'new-slug',
            'visibility' => $group->visibility->value,
        ]);

    $group->refresh();
    expect($group->slug)->toBe('new-slug');
});

it('prevents duplicate slugs', function (): void {
    Queue::fake();

    $existingGroup = Group::factory()->create(['slug' => 'taken-slug']);
    $group = Group::factory()->create(['slug' => 'my-slug']);
    $coOrganizer = User::factory()->create();

    $group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($coOrganizer)
        ->put(route('groups.manage.settings.update', $group), [
            'name' => $group->name,
            'slug' => 'taken-slug',
            'visibility' => $group->visibility->value,
        ])
        ->assertSessionHasErrors('slug');
});

it('allows keeping the same slug', function (): void {
    Queue::fake();

    $group = Group::factory()->create(['slug' => 'my-slug']);
    $coOrganizer = User::factory()->create();

    $group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($coOrganizer)
        ->put(route('groups.manage.settings.update', $group), [
            'name' => $group->name,
            'slug' => 'my-slug',
            'visibility' => $group->visibility->value,
        ])
        ->assertSessionHasNoErrors();
});

it('creates new membership questions', function (): void {
    Queue::fake();

    $group = Group::factory()->create();
    $coOrganizer = User::factory()->create();

    $group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($coOrganizer)
        ->put(route('groups.manage.settings.update', $group), [
            'name' => $group->name,
            'slug' => $group->slug,
            'visibility' => $group->visibility->value,
            'membership_questions' => [
                ['question' => 'Why do you want to join?', 'is_required' => true],
                ['question' => 'What is your experience?', 'is_required' => false],
            ],
        ]);

    $questions = $group->membershipQuestions()->orderBy('sort_order')->get();
    expect($questions)->toHaveCount(2);
    expect($questions[0]->question)->toBe('Why do you want to join?');
    expect($questions[0]->is_required)->toBeTrue();
    expect($questions[0]->sort_order)->toBe(0);
    expect($questions[1]->question)->toBe('What is your experience?');
    expect($questions[1]->is_required)->toBeFalse();
    expect($questions[1]->sort_order)->toBe(1);
});

it('updates existing membership questions', function (): void {
    Queue::fake();

    $group = Group::factory()->create();
    $coOrganizer = User::factory()->create();

    $group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    $question = $group->membershipQuestions()->create([
        'question' => 'Original question',
        'is_required' => true,
        'sort_order' => 0,
    ]);

    $this->actingAs($coOrganizer)
        ->put(route('groups.manage.settings.update', $group), [
            'name' => $group->name,
            'slug' => $group->slug,
            'visibility' => $group->visibility->value,
            'membership_questions' => [
                ['id' => $question->id, 'question' => 'Updated question', 'is_required' => false],
            ],
        ]);

    $question->refresh();
    expect($question->question)->toBe('Updated question');
    expect($question->is_required)->toBeFalse();
});

it('deletes removed membership questions', function (): void {
    Queue::fake();

    $group = Group::factory()->create();
    $coOrganizer = User::factory()->create();

    $group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    $question1 = $group->membershipQuestions()->create([
        'question' => 'Question one',
        'is_required' => true,
        'sort_order' => 0,
    ]);

    $question2 = $group->membershipQuestions()->create([
        'question' => 'Question two',
        'is_required' => false,
        'sort_order' => 1,
    ]);

    // Submit without question2 — it should be deleted
    $this->actingAs($coOrganizer)
        ->put(route('groups.manage.settings.update', $group), [
            'name' => $group->name,
            'slug' => $group->slug,
            'visibility' => $group->visibility->value,
            'membership_questions' => [
                ['id' => $question1->id, 'question' => 'Question one', 'is_required' => true],
            ],
        ]);

    expect($group->membershipQuestions()->count())->toBe(1);
    expect(GroupMembershipQuestion::find($question2->id))->toBeNull();
});

it('reorders membership questions via sort_order', function (): void {
    Queue::fake();

    $group = Group::factory()->create();
    $coOrganizer = User::factory()->create();

    $group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    $q1 = $group->membershipQuestions()->create([
        'question' => 'First',
        'is_required' => true,
        'sort_order' => 0,
    ]);

    $q2 = $group->membershipQuestions()->create([
        'question' => 'Second',
        'is_required' => true,
        'sort_order' => 1,
    ]);

    // Swap order
    $this->actingAs($coOrganizer)
        ->put(route('groups.manage.settings.update', $group), [
            'name' => $group->name,
            'slug' => $group->slug,
            'visibility' => $group->visibility->value,
            'membership_questions' => [
                ['id' => $q2->id, 'question' => 'Second', 'is_required' => true],
                ['id' => $q1->id, 'question' => 'First', 'is_required' => true],
            ],
        ]);

    $q1->refresh();
    $q2->refresh();
    expect($q2->sort_order)->toBe(0);
    expect($q1->sort_order)->toBe(1);
});

it('dispatches geocoding job when location changes', function (): void {
    $group = Group::factory()->create(['location' => 'Copenhagen, Denmark']);
    $coOrganizer = User::factory()->create();

    $group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    Queue::fake();

    $this->actingAs($coOrganizer)
        ->put(route('groups.manage.settings.update', $group), [
            'name' => $group->name,
            'slug' => $group->slug,
            'location' => 'Berlin, Germany',
            'visibility' => $group->visibility->value,
        ]);

    Queue::assertPushed(GeocodeLocation::class, function (GeocodeLocation $job) use ($group) {
        return $job->model->is($group);
    });
});

it('rejects member from updating settings', function (): void {
    $group = Group::factory()->create();
    $member = User::factory()->create();

    $group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($member)
        ->put(route('groups.manage.settings.update', $group), [
            'name' => 'Hacked Name',
            'slug' => $group->slug,
            'visibility' => 'public',
        ])
        ->assertForbidden();

    $group->refresh();
    expect($group->name)->not->toBe('Hacked Name');
});
