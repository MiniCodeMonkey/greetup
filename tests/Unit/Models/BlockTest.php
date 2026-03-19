<?php

use App\Models\Block;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('can be created using the factory', function (): void {
    $block = Block::factory()->create();

    expect($block)->toBeInstanceOf(Block::class)
        ->and($block->exists)->toBeTrue();
});

it('has blocker belongsTo relationship', function (): void {
    $block = Block::factory()->create();

    expect($block->blocker())->toBeInstanceOf(BelongsTo::class)
        ->and($block->blocker)->toBeInstanceOf(User::class);
});

it('has blocked belongsTo relationship', function (): void {
    $block = Block::factory()->create();

    expect($block->blocked())->toBeInstanceOf(BelongsTo::class)
        ->and($block->blocked)->toBeInstanceOf(User::class);
});

it('enforces unique constraint on blocker and blocked pair', function (): void {
    $blocker = User::factory()->create();
    $blocked = User::factory()->create();

    Block::factory()->create([
        'blocker_id' => $blocker->id,
        'blocked_id' => $blocked->id,
    ]);

    Block::factory()->create([
        'blocker_id' => $blocker->id,
        'blocked_id' => $blocked->id,
    ]);
})->throws(UniqueConstraintViolationException::class);

it('casts created_at to datetime', function (): void {
    $block = Block::factory()->create();
    $block->refresh();

    expect($block->created_at)->toBeInstanceOf(Carbon::class);
});
