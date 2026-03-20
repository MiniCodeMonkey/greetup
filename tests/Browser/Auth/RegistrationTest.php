<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;

uses(DatabaseMigrations::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('user can register, see verification notice, and reach dashboard after verification', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
            ->assertSee('Create an account')
            ->type('name', 'Jane Doe')
            ->type('email', 'jane@example.com')
            ->type('password', 'password123')
            ->type('password_confirmation', 'password123')
            ->press('Create account')
            ->waitForLocation('/email/verify')
            ->assertSee('Check your email');

        // Verify email manually via database
        $user = User::where('email', 'jane@example.com')->first();
        $user->markEmailAsVerified();

        $browser->visit('/dashboard')
            ->assertSee('Jane Doe');
    });
});

test('registration form shows validation errors for missing fields', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
            ->type('name', '')
            ->type('email', 'invalid-email')
            ->type('password', 'short')
            ->type('password_confirmation', 'different')
            ->press('Create account')
            ->assertSee('The name field is required');
    });
});
