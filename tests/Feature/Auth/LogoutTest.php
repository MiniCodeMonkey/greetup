<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs out an authenticated user and redirects to homepage', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    $this->assertAuthenticatedAs($user);

    $response = $this->post(route('logout'));

    $response->assertRedirect('/');
    $this->assertGuest();
});

it('invalidates the session on logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    $sessionId = session()->getId();

    $this->post(route('logout'));

    expect(session()->getId())->not->toBe($sessionId);
});

it('redirects unauthenticated users who try to logout', function () {
    $this->post(route('logout'))
        ->assertRedirect('/login');
});
