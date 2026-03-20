<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;

uses(DatabaseMigrations::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('user can request a password reset link', function () {
    User::factory()->create(['email' => 'reset@example.com']);

    $this->browse(function (Browser $browser) {
        $browser->visit('/forgot-password')
            ->assertSee('Forgot your password?')
            ->type('email', 'reset@example.com')
            ->press('Send reset link')
            ->waitForText('We have emailed your password reset link');
    });
});

test('user can reset password via token and login with new password', function () {
    $user = User::factory()->create([
        'email' => 'reset@example.com',
        'name' => 'Reset User',
    ]);

    // Create a password reset token manually
    $token = Str::random(64);
    DB::table('password_reset_tokens')->insert([
        'email' => 'reset@example.com',
        'token' => Hash::make($token),
        'created_at' => now(),
    ]);

    $this->browse(function (Browser $browser) use ($token) {
        $browser->visit('/reset-password/'.$token.'?email=reset%40example.com')
            ->assertSee('Reset your password')
            ->type('password', 'newpassword123')
            ->type('password_confirmation', 'newpassword123')
            ->press('Reset password')
            ->waitForLocation('/login');

        // Login with new password
        $browser->type('email', 'reset@example.com')
            ->type('password', 'newpassword123')
            ->press('Log in')
            ->waitForLocation('/dashboard')
            ->assertSee('Reset User');
    });
});
