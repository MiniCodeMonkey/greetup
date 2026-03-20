<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;

uses(DatabaseMigrations::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('user can log in and see dashboard with their name', function () {
    $user = User::factory()->create([
        'name' => 'John Smith',
        'email' => 'john@example.com',
    ]);

    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->assertSee('Log in to your account')
            ->type('email', 'john@example.com')
            ->type('password', 'password')
            ->press('Log in')
            ->waitForLocation('/dashboard')
            ->assertSee('John Smith');
    });
});

test('login fails with invalid credentials', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->type('email', 'wrong@example.com')
            ->type('password', 'wrongpassword')
            ->press('Log in')
            ->assertPathIs('/login')
            ->assertSee('These credentials do not match');
    });
});
