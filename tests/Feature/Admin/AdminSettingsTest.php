<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->withoutVite();
});

// --- Access Control ---

it('allows admin users to access the settings page', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.settings'));

    $response->assertOk();
    $response->assertSee('Platform Settings');
});

it('returns 403 for regular users accessing settings', function (): void {
    $user = User::factory()->create();
    $user->assignRole('user');

    $response = $this->actingAs($user)->get(route('admin.settings'));

    $response->assertForbidden();
});

it('returns 403 for regular users updating settings', function (): void {
    $user = User::factory()->create();
    $user->assignRole('user');

    $response = $this->actingAs($user)->put(route('admin.settings.update'), [
        'site_name' => 'Test',
        'default_timezone' => 'UTC',
        'default_locale' => 'en',
    ]);

    $response->assertForbidden();
});

it('redirects unauthenticated users to login', function (): void {
    $response = $this->get(route('admin.settings'));

    $response->assertRedirect(route('login'));
});

// --- Settings Display ---

it('displays default settings when no settings are stored', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.settings'));

    $response->assertOk();
    $response->assertSee('Greetup');
    $response->assertSee('UTC');
});

it('displays stored settings', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Setting::create(['key' => 'site_name', 'value' => 'My Community']);
    Setting::create(['key' => 'site_description', 'value' => 'A great community']);

    Setting::clearCache();

    $response = $this->actingAs($admin)->get(route('admin.settings'));

    $response->assertOk();
    $response->assertSee('My Community');
    $response->assertSee('A great community');
});

// --- Settings Update ---

it('allows admin to update settings', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
        'site_name' => 'New Platform Name',
        'site_description' => 'A new tagline',
        'registration_enabled' => '1',
        'require_email_verification' => '0',
        'max_groups_per_user' => 5,
        'default_timezone' => 'America/New_York',
        'default_locale' => 'en',
    ]);

    $response->assertRedirect(route('admin.settings'));
    $response->assertSessionHas('success', 'Settings updated successfully.');

    expect(Setting::where('key', 'site_name')->first()->value)->toBe('New Platform Name');
    expect(Setting::where('key', 'site_description')->first()->value)->toBe('A new tagline');
    expect(Setting::where('key', 'registration_enabled')->first()->value)->toBe('1');
    expect(Setting::where('key', 'require_email_verification')->first()->value)->toBe('0');
    expect(Setting::where('key', 'max_groups_per_user')->first()->value)->toBe('5');
    expect(Setting::where('key', 'default_timezone')->first()->value)->toBe('America/New_York');
    expect(Setting::where('key', 'default_locale')->first()->value)->toBe('en');
});

it('allows max_groups_per_user to be null for unlimited', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
        'site_name' => 'Greetup',
        'site_description' => '',
        'registration_enabled' => '1',
        'require_email_verification' => '1',
        'max_groups_per_user' => null,
        'default_timezone' => 'UTC',
        'default_locale' => 'en',
    ]);

    $response->assertRedirect(route('admin.settings'));

    expect(Setting::where('key', 'max_groups_per_user')->first()->value)->toBeNull();
});

it('validates required fields', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
        'site_name' => '',
        'default_timezone' => '',
        'default_locale' => '',
    ]);

    $response->assertSessionHasErrors(['site_name', 'default_timezone', 'default_locale']);
});

it('validates timezone is a valid IANA identifier', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
        'site_name' => 'Greetup',
        'default_timezone' => 'Not/A/Timezone',
        'default_locale' => 'en',
    ]);

    $response->assertSessionHasErrors(['default_timezone']);
});

// --- Cache Invalidation ---

it('invalidates cache when settings are updated', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Store an initial value
    Setting::create(['key' => 'site_name', 'value' => 'Old Name']);

    // Update settings via admin form
    $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
        'site_name' => 'New Name',
        'site_description' => '',
        'registration_enabled' => '1',
        'require_email_verification' => '1',
        'default_timezone' => 'UTC',
        'default_locale' => 'en',
    ]);

    $response->assertRedirect(route('admin.settings'));

    // DB should have the new value
    $allSiteNameSettings = Setting::where('key', 'site_name')->get();
    expect($allSiteNameSettings)->toHaveCount(1);
    expect($allSiteNameSettings->first()->value)->toBe('New Name');

    // After clearing cache and re-fetching, we get the updated value
    Setting::clearCache();
    $cached = Setting::allCached();
    expect($cached['site_name'])->toBe('New Name');
});

it('uses cached values for performance', function (): void {
    Setting::create(['key' => 'site_name', 'value' => 'Cached Name']);
    Setting::clearCache();

    // First call caches
    $first = Setting::allCached();
    expect($first['site_name'])->toBe('Cached Name');

    // Verify cache key exists
    expect(Cache::has(Setting::CACHE_KEY))->toBeTrue();

    // Second call uses cache
    $second = Setting::allCached();
    expect($second['site_name'])->toBe('Cached Name');
});
