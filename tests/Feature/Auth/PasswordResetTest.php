<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('displays the forgot password form', function () {
    $this->get('/forgot-password')
        ->assertStatus(200)
        ->assertSee('Forgot your password?');
});

it('sends a password reset link to a valid email', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'jane@example.com',
    ]);

    $this->post('/forgot-password', [
        'email' => 'jane@example.com',
    ])->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

it('does not reveal whether email exists', function () {
    Notification::fake();

    $this->post('/forgot-password', [
        'email' => 'nonexistent@example.com',
    ]);

    Notification::assertNothingSent();
});

it('displays the reset password form', function () {
    $this->get('/reset-password/test-token')
        ->assertStatus(200)
        ->assertSee('Reset your password');
});

it('resets the password with a valid token', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
    ]);

    $token = Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'jane@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertRedirect(route('login'));

    $user->refresh();
    expect(Hash::check('newpassword123', $user->password))->toBeTrue();
});

it('rejects an expired token', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
    ]);

    $token = Password::createToken($user);

    // Travel past the 60-minute expiry
    $this->travel(61)->minutes();

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'jane@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertSessionHasErrors('email');
});

it('rejects an invalid token', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
    ]);

    $this->post('/reset-password', [
        'token' => 'invalid-token',
        'email' => 'jane@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertSessionHasErrors('email');
});

it('validates password confirmation on reset', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
    ]);

    $token = Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'jane@example.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'differentpassword',
    ])->assertSessionHasErrors('password');
});

it('validates password minimum length on reset', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
    ]);

    $token = Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'jane@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors('password');
});
