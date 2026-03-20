<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;

uses(DatabaseMigrations::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('user can create a group and see the group page', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/groups/create')
            ->assertSee('Create')
            ->type('name', 'Dusk Test Group')
            ->type('description', 'A group created during browser testing.')
            ->type('location', 'Copenhagen, Denmark')
            ->radio('visibility', 'public')
            ->press('Create Group')
            ->waitForText('Dusk Test Group')
            ->assertSee('Dusk Test Group')
            ->assertSee('A group created during browser testing.');
    });
});

test('group create form validates required fields', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/groups/create')
            ->press('Create Group')
            ->assertSee('The name field is required');
    });
});
