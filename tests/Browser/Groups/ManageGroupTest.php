<?php

use App\Models\Group;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;

uses(DatabaseMigrations::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('organizer can navigate management pages', function () {
    $organizer = User::factory()->create(['name' => 'Group Organizer']);
    $group = Group::factory()->create([
        'name' => 'Managed Group',
        'organizer_id' => $organizer->id,
    ]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);

    $this->browse(function (Browser $browser) use ($organizer, $group) {
        $browser->loginAs($organizer)
            ->visit('/groups/'.$group->slug.'/manage/settings')
            ->assertSee('Managed Group')
            ->assertSee('Settings');

        $browser->visit('/groups/'.$group->slug.'/manage/members')
            ->assertSee('Group Organizer');
    });
});

test('organizer can change group settings', function () {
    $organizer = User::factory()->create();
    $group = Group::factory()->create([
        'name' => 'Settings Group',
        'organizer_id' => $organizer->id,
    ]);
    $group->members()->attach($organizer->id, [
        'role' => 'organizer',
        'joined_at' => now(),
    ]);

    $this->browse(function (Browser $browser) use ($organizer, $group) {
        $browser->loginAs($organizer)
            ->visit('/groups/'.$group->slug.'/manage/settings')
            ->type('name', 'Updated Group Name')
            ->press('Save Settings')
            ->waitForText('Updated Group Name');
    });
});
