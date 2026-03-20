<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Tags\Tag;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->withoutVite();
});

// --- Access Control ---

it('allows admin to access interest list', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.interests.index'));

    $response->assertOk();
    $response->assertSee('Manage Interests');
});

it('returns 403 for regular users accessing interest list', function (): void {
    $user = User::factory()->create();
    $user->assignRole('user');

    $response = $this->actingAs($user)->get(route('admin.interests.index'));

    $response->assertForbidden();
});

it('redirects unauthenticated users to login for interest list', function (): void {
    $response = $this->get(route('admin.interests.index'));

    $response->assertRedirect(route('login'));
});

// --- CRUD: Create ---

it('shows the create interest form', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.interests.create'));

    $response->assertOk();
    $response->assertSee('Create Interest');
});

it('creates a new interest', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('admin.interests.store'), [
        'name' => 'Machine Learning',
    ]);

    $response->assertRedirect(route('admin.interests.index'));
    $response->assertSessionHas('success');

    $tag = Tag::where('type', 'interest')->where('name->en', 'Machine Learning')->first();
    expect($tag)->not->toBeNull();
});

it('prevents creating a duplicate interest', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Tag::findOrCreate('Photography', 'interest');

    $response = $this->actingAs($admin)->post(route('admin.interests.store'), [
        'name' => 'Photography',
    ]);

    $response->assertSessionHasErrors('name');
});

// --- CRUD: Read (Index) ---

it('displays interests with usage count', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $tag = Tag::findOrCreate('Hiking', 'interest');

    // Attach to a user
    $user = User::factory()->create();
    $user->attachTag($tag);

    // Attach to a group
    $group = Group::factory()->create();
    $group->attachTag($tag);

    $response = $this->actingAs($admin)->get(route('admin.interests.index'));

    $response->assertOk();
    $response->assertSee('Hiking');
    $response->assertSee('2'); // usage count: 1 user + 1 group
});

it('searches interests by name', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Tag::findOrCreate('Photography', 'interest');
    Tag::findOrCreate('Cooking', 'interest');

    $response = $this->actingAs($admin)->get(route('admin.interests.index', ['search' => 'Photo']));

    $response->assertOk();
    $response->assertSee('Photography');
    $response->assertDontSee('Cooking');
});

// --- CRUD: Update ---

it('shows the edit interest form', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $tag = Tag::findOrCreate('Old Name', 'interest');

    $response = $this->actingAs($admin)->get(route('admin.interests.edit', $tag));

    $response->assertOk();
    $response->assertSee('Edit Interest');
    $response->assertSee('Old Name');
});

it('updates an interest name', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $tag = Tag::findOrCreate('Old Name', 'interest');

    $response = $this->actingAs($admin)->put(route('admin.interests.update', $tag), [
        'name' => 'New Name',
    ]);

    $response->assertRedirect(route('admin.interests.index'));
    $response->assertSessionHas('success');

    expect($tag->fresh()->name)->toBe('New Name');
});

// --- CRUD: Delete ---

it('deletes an interest', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $tag = Tag::findOrCreate('To Delete', 'interest');
    $tagId = $tag->id;

    $response = $this->actingAs($admin)->delete(route('admin.interests.destroy', $tag));

    $response->assertRedirect(route('admin.interests.index'));
    $response->assertSessionHas('success');

    expect(Tag::find($tagId))->toBeNull();
});

// --- Merge ---

it('merges an interest into another and reassigns relationships', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $source = Tag::findOrCreate('Source Interest', 'interest');
    $target = Tag::findOrCreate('Target Interest', 'interest');

    // Attach source to a user and group
    $user = User::factory()->create();
    $user->attachTag($source);

    $group = Group::factory()->create();
    $group->attachTag($source);

    $response = $this->actingAs($admin)->post(route('admin.interests.merge', $source), [
        'target_id' => $target->id,
    ]);

    $response->assertRedirect(route('admin.interests.index'));
    $response->assertSessionHas('success');

    // Source tag should be deleted
    expect(Tag::find($source->id))->toBeNull();

    // Relationships should now point to target
    $targetTaggableCount = DB::table('taggables')
        ->where('tag_id', $target->id)
        ->count();
    expect($targetTaggableCount)->toBe(2);

    // No orphaned source taggables
    $sourceTaggableCount = DB::table('taggables')
        ->where('tag_id', $source->id)
        ->count();
    expect($sourceTaggableCount)->toBe(0);
});

it('handles merge when both tags are attached to the same entity', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $source = Tag::findOrCreate('Duplicate Source', 'interest');
    $target = Tag::findOrCreate('Duplicate Target', 'interest');

    // Attach both source and target to the same user
    $user = User::factory()->create();
    $user->attachTag($source);
    $user->attachTag($target);

    $response = $this->actingAs($admin)->post(route('admin.interests.merge', $source), [
        'target_id' => $target->id,
    ]);

    $response->assertRedirect(route('admin.interests.index'));

    // Source tag deleted
    expect(Tag::find($source->id))->toBeNull();

    // Target should still have exactly 1 relationship to this user (no duplicates)
    $targetTaggableCount = DB::table('taggables')
        ->where('tag_id', $target->id)
        ->where('taggable_type', User::class)
        ->where('taggable_id', $user->id)
        ->count();
    expect($targetTaggableCount)->toBe(1);
});

it('prevents merging an interest into itself', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $tag = Tag::findOrCreate('Self Merge', 'interest');

    $response = $this->actingAs($admin)->post(route('admin.interests.merge', $tag), [
        'target_id' => $tag->id,
    ]);

    $response->assertSessionHasErrors('target_id');
});

// --- Usage Count ---

it('shows correct usage count for groups and users', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $tag = Tag::findOrCreate('Popular Interest', 'interest');

    // 3 users
    $users = User::factory()->count(3)->create();
    foreach ($users as $user) {
        $user->attachTag($tag);
    }

    // 2 groups
    $groups = Group::factory()->count(2)->create();
    foreach ($groups as $group) {
        $group->attachTag($tag);
    }

    $response = $this->actingAs($admin)->get(route('admin.interests.index'));

    $response->assertOk();
    $response->assertSee('Popular Interest');
    $response->assertSee('5'); // 3 users + 2 groups
});
