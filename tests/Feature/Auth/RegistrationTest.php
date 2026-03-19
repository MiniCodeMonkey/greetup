<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('displays the registration form', function () {
    $this->get('/register')
        ->assertStatus(200)
        ->assertSee('Create an account');
});

it('registers a user with valid data', function () {
    Event::fake();

    $response = $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'jane@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Jane Doe')
        ->and($user->hasRole('user'))->toBeTrue();

    $this->assertAuthenticated();

    Event::assertDispatched(Registered::class, function ($event) use ($user) {
        return $event->user->id === $user->id;
    });
});

it('requires a name', function () {
    $this->post('/register', [
        'name' => '',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('name');
});

it('requires an email', function () {
    $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => '',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('email');
});

it('requires a valid email', function () {
    $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => 'not-an-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('email');
});

it('requires a unique email', function () {
    User::factory()->create(['email' => 'jane@example.com']);

    $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('email');
});

it('requires a password with at least 8 characters', function () {
    $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors('password');
});

it('requires password confirmation to match', function () {
    $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different123',
    ])->assertSessionHasErrors('password');
});

it('requires all fields', function () {
    $this->post('/register', [])
        ->assertSessionHasErrors(['name', 'email', 'password']);
});

it('rate limits registration to 5 per hour per IP', function () {
    for ($i = 1; $i <= 5; $i++) {
        $this->post('/register', [
            'name' => "User {$i}",
            'email' => "user{$i}@example.com",
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('verification.notice'));

        auth()->logout();
    }

    $this->post('/register', [
        'name' => 'User 6',
        'email' => 'user6@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(429);
});

it('redirects authenticated users away from registration', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/register')
        ->assertRedirect('/');
});

it('enforces name max length of 255', function () {
    $this->post('/register', [
        'name' => str_repeat('a', 256),
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('name');
});
