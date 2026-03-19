<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('shows the verification notice page to unverified users', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get('/email/verify')
        ->assertStatus(200)
        ->assertSee('Check your email')
        ->assertSee('Resend verification email');
});

it('redirects guests away from verification notice page', function () {
    $this->get('/email/verify')
        ->assertRedirect('/login');
});

it('verifies user email when clicking valid verification link', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
    );

    $this->actingAs($user)
        ->get($verificationUrl)
        ->assertRedirect('/dashboard');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects expired verification links', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->subMinutes(1),
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
    );

    $this->actingAs($user)
        ->get($verificationUrl)
        ->assertStatus(403);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects verification links with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => 'invalid-hash']
    );

    $this->actingAs($user)
        ->get($verificationUrl)
        ->assertStatus(403);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('allows resending verification email', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post('/email/verification-notification')
        ->assertRedirect()
        ->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('rate limits resend verification to 6 per minute', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    for ($i = 0; $i < 6; $i++) {
        $this->actingAs($user)
            ->post('/email/verification-notification')
            ->assertRedirect();
    }

    $this->actingAs($user)
        ->post('/email/verification-notification')
        ->assertStatus(429);
});

it('shows verification banner for unverified users', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get('/email/verify')
        ->assertSee('Your email is not verified')
        ->assertSee('Verify your email');
});

it('does not show verification banner for verified users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/email/verify')
        ->assertDontSee('Your email is not verified');
});

it('blocks unverified users from joining groups with verified middleware', function () {
    $user = User::factory()->unverified()->create();

    // Register a test route protected by verified middleware to prove it works
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/test-verified-route', function () {
            return 'OK';
        })->name('test.verified');
    });

    $this->actingAs($user)
        ->get('/test-verified-route')
        ->assertRedirect('/email/verify');
});

it('allows verified users through verified middleware', function () {
    $user = User::factory()->create();

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/test-verified-route', function () {
            return 'OK';
        })->name('test.verified');
    });

    $this->actingAs($user)
        ->get('/test-verified-route')
        ->assertStatus(200)
        ->assertSee('OK');
});

it('configures verification token to expire after 60 minutes', function () {
    expect(Config::get('auth.verification.expire'))->toBe(60);
});

it('does not show verification banner to guests on public pages', function () {
    // Guests can't access /email/verify (redirects to login), so verify on the register page
    $this->get('/register')
        ->assertDontSee('Your email is not verified');
});

it('shows success message after resending verification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post('/email/verification-notification');

    $this->actingAs($user)
        ->get('/email/verify')
        ->assertSee('A new verification link has been sent');
});
