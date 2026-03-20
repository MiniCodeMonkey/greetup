<?php

use App\Models\Setting;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user']);
});

it('redirects suspended user to suspension page', function () {
    $user = User::factory()->suspended()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('suspended'));
});

it('shows suspension reason on suspended page', function () {
    $reason = 'Violation of community guidelines';
    $user = User::factory()->suspended()->create(['suspended_reason' => $reason]);

    $response = $this->actingAs($user)->get(route('suspended'));

    $response->assertOk();
    $response->assertSeeText($reason);
});

it('shows logout link on suspended page', function () {
    $user = User::factory()->suspended()->create();

    $response = $this->actingAs($user)->get(route('suspended'));

    $response->assertOk();
    $response->assertSee('Log out');
    $response->assertSee(route('logout'));
});

it('allows non-suspended user to access protected routes', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
});

it('allows suspended user to logout', function () {
    $user = User::factory()->suspended()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect('/');
    $this->assertGuest();
});

it('applies to all authenticated routes', function () {
    $user = User::factory()->suspended()->create();

    // Email verification routes should also redirect suspended users
    $response = $this->actingAs($user)->get(route('verification.notice'));

    $response->assertRedirect(route('suspended'));
});

it('shows minimal nav with only logo and logout', function () {
    $user = User::factory()->suspended()->create();

    $response = $this->actingAs($user)->get(route('suspended'));

    $response->assertOk();
    $response->assertSee(asset('images/greetup.png'));
    $response->assertSee('Log out');
    $response->assertDontSee('Explore');
    $response->assertDontSee('Groups');
    $response->assertDontSee('Dashboard');
});

it('shows contact support link when configured in platform settings', function () {
    Setting::query()->updateOrCreate(['key' => 'support_url'], ['value' => 'https://support.example.com']);
    Setting::clearCache();

    $user = User::factory()->suspended()->create();

    $response = $this->actingAs($user)->get(route('suspended'));

    $response->assertOk();
    $response->assertSee('Contact support');
    $response->assertSee('https://support.example.com');
});

it('hides contact support link when not configured', function () {
    Setting::query()->where('key', 'support_url')->delete();
    Setting::clearCache();

    $user = User::factory()->suspended()->create();

    $response = $this->actingAs($user)->get(route('suspended'));

    $response->assertOk();
    $response->assertDontSee('Contact support');
});
