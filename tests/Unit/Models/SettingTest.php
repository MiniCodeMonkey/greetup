<?php

use App\Models\Setting;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created using the factory', function (): void {
    $setting = Setting::factory()->create();

    expect($setting)->toBeInstanceOf(Setting::class)
        ->and($setting->exists)->toBeTrue();
});

it('has a unique key', function (): void {
    Setting::factory()->create(['key' => 'site_name']);

    Setting::factory()->create(['key' => 'site_name']);
})->throws(UniqueConstraintViolationException::class);

it('allows nullable value', function (): void {
    $setting = Setting::factory()->create(['key' => 'empty_setting', 'value' => null]);

    expect($setting->value)->toBeNull();
});

it('stores and retrieves string value', function (): void {
    $setting = Setting::factory()->create(['key' => 'site_name', 'value' => 'Greetup']);
    $setting->refresh();

    expect($setting->value)->toBe('Greetup');
});
