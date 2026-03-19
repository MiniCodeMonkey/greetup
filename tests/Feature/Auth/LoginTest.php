<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('displays the login form', function () {
    $this->get('/login')
        ->assertStatus(200)
        ->assertSee('Log in to your account');
});

it('logs in a user with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    $response = $this->post('/login', [
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    $this->post('/login', [
        'email' => 'jane@example.com',
        'password' => 'wrongpassword',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('rate limits login to 5 failed attempts per minute', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    for ($i = 1; $i <= 5; $i++) {
        $this->post('/login', [
            'email' => 'jane@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    $this->postJson('/login', [
        'email' => 'jane@example.com',
        'password' => 'wrongpassword',
    ])->assertStatus(429);
});

it('redirects suspended users to the suspended page', function () {
    $user = User::factory()->suspended('You violated our rules')->create([
        'email' => 'suspended@example.com',
        'password' => 'password123',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('suspended'));
});

it('shows the suspended page with reason', function () {
    $user = User::factory()->suspended('You violated our rules')->create();

    $this->actingAs($user)
        ->get(route('suspended'))
        ->assertStatus(200)
        ->assertSee('Your account has been suspended')
        ->assertSee('You violated our rules');
});

it('remembers the user when remember me is checked', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    $response = $this->post('/login', [
        'email' => 'jane@example.com',
        'password' => 'password123',
        'remember' => 'on',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);

    // Verify the remember token was set
    $user->refresh();
    expect($user->remember_token)->not->toBeNull();
});
