<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows a user to delete their account with correct password', function () {
    $user = User::factory()->create([
        'password' => 'password123',
    ]);

    $response = $this->actingAs($user)
        ->delete('/settings/account', [
            'password' => 'password123',
        ]);

    $response->assertRedirect('/');
    $this->assertGuest();
    $this->assertSoftDeleted('users', ['id' => $user->id]);
});

it('rejects account deletion with wrong password', function () {
    $user = User::factory()->create([
        'password' => 'password123',
    ]);

    $response = $this->actingAs($user)
        ->delete('/settings/account', [
            'password' => 'wrongpassword',
        ]);

    $response->assertSessionHasErrors('password');
    $this->assertAuthenticatedAs($user);
    $this->assertNotSoftDeleted('users', ['id' => $user->id]);
});

it('prevents a soft-deleted user from logging in', function () {
    $user = User::factory()->create([
        'email' => 'deleted@example.com',
        'password' => 'password123',
    ]);

    $user->delete();

    $this->post('/login', [
        'email' => 'deleted@example.com',
        'password' => 'password123',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
