<?php

use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('can be created using the factory', function (): void {
    $discussion = Discussion::factory()->create();

    expect($discussion)->toBeInstanceOf(Discussion::class)
        ->and($discussion->exists)->toBeTrue()
        ->and($discussion->title)->toBeString()
        ->and($discussion->slug)->toBeString()
        ->and($discussion->body)->toBeString()
        ->and($discussion->is_pinned)->toBeFalse()
        ->and($discussion->is_locked)->toBeFalse()
        ->and($discussion->last_activity_at)->not->toBeNull();
});

it('has group belongsTo relationship', function (): void {
    $discussion = Discussion::factory()->create();

    expect($discussion->group())->toBeInstanceOf(BelongsTo::class)
        ->and($discussion->group)->toBeInstanceOf(Group::class);
});

it('has user belongsTo relationship', function (): void {
    $discussion = Discussion::factory()->create();

    expect($discussion->user())->toBeInstanceOf(BelongsTo::class)
        ->and($discussion->user)->toBeInstanceOf(User::class);
});

it('has replies hasMany relationship', function (): void {
    $discussion = Discussion::factory()->create();
    DiscussionReply::factory()->count(3)->create(['discussion_id' => $discussion->id]);

    expect($discussion->replies())->toBeInstanceOf(HasMany::class)
        ->and($discussion->replies)->toHaveCount(3);
});

it('generates a slug from title', function (): void {
    $discussion = Discussion::factory()->create(['title' => 'My First Discussion']);

    expect($discussion->slug)->toBe('my-first-discussion');
});

it('generates unique slug within group', function (): void {
    $group = Group::factory()->create();

    $discussion1 = Discussion::factory()->create(['group_id' => $group->id, 'title' => 'Same Title']);
    $discussion2 = Discussion::factory()->create(['group_id' => $group->id, 'title' => 'Same Title']);

    expect($discussion1->slug)->toBe('same-title')
        ->and($discussion2->slug)->not->toBe('same-title');
});

it('allows same slug in different groups', function (): void {
    $group1 = Group::factory()->create();
    $group2 = Group::factory()->create();

    $discussion1 = Discussion::factory()->create(['group_id' => $group1->id, 'title' => 'Same Title']);
    $discussion2 = Discussion::factory()->create(['group_id' => $group2->id, 'title' => 'Same Title']);

    expect($discussion1->slug)->toBe('same-title')
        ->and($discussion2->slug)->toBe('same-title');
});

it('enforces unique constraint on group_id and slug', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    Discussion::factory()->create(['group_id' => $group->id, 'title' => 'Test']);

    // Insert directly via DB to bypass slug generation and test the constraint
    DB::table('discussions')->insert([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'title' => 'Other',
        'slug' => 'test',
        'body' => 'test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
})->throws(QueryException::class);

it('casts is_pinned to boolean', function (): void {
    $discussion = Discussion::factory()->create();

    expect($discussion->is_pinned)->toBeBool()->toBeFalse();
});

it('casts is_locked to boolean', function (): void {
    $discussion = Discussion::factory()->create();

    expect($discussion->is_locked)->toBeBool()->toBeFalse();
});

it('casts last_activity_at to datetime', function (): void {
    $discussion = Discussion::factory()->create();

    expect($discussion->last_activity_at)->toBeInstanceOf(Carbon\Carbon::class);
});

it('uses soft deletes', function (): void {
    $discussion = Discussion::factory()->create();

    expect(in_array(SoftDeletingScope::class, array_map(
        fn ($scope) => $scope::class,
        $discussion->getGlobalScopes()
    )))->toBeTrue();

    $discussion->delete();

    expect($discussion->trashed())->toBeTrue()
        ->and(Discussion::withTrashed()->find($discussion->id))->not->toBeNull()
        ->and(Discussion::find($discussion->id))->toBeNull();
});

it('scopes pinnedFirst orders pinned first then by last_activity_at desc', function (): void {
    $group = Group::factory()->create();

    $old = Discussion::factory()->create([
        'group_id' => $group->id,
        'is_pinned' => false,
        'last_activity_at' => now()->subDays(2),
    ]);

    $recent = Discussion::factory()->create([
        'group_id' => $group->id,
        'is_pinned' => false,
        'last_activity_at' => now(),
    ]);

    $pinned = Discussion::factory()->create([
        'group_id' => $group->id,
        'is_pinned' => true,
        'last_activity_at' => now()->subDays(5),
    ]);

    $results = Discussion::query()->pinnedFirst()->pluck('id');

    expect($results->first())->toBe($pinned->id)
        ->and($results[1])->toBe($recent->id)
        ->and($results->last())->toBe($old->id);
});

it('has pinned factory state', function (): void {
    $discussion = Discussion::factory()->pinned()->create();

    expect($discussion->is_pinned)->toBeTrue();
});

it('has locked factory state', function (): void {
    $discussion = Discussion::factory()->locked()->create();

    expect($discussion->is_locked)->toBeTrue();
});
