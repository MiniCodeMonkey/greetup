<?php

use App\Jobs\GeocodeLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Tags\Tag;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('displays the settings page with profile section', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings')
        ->assertStatus(200)
        ->assertSee('Profile Information')
        ->assertSee($user->name);
});

it('displays the settings page with account section', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings?section=account')
        ->assertStatus(200)
        ->assertSee('Email Address')
        ->assertSee('Update Password');
});

it('displays the settings page with notifications section', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings?section=notifications')
        ->assertStatus(200)
        ->assertSee('Notification Preferences');
});

it('displays the settings page with privacy section', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings?section=privacy')
        ->assertStatus(200)
        ->assertSee('Privacy Settings');
});

it('updates the user name', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $this->actingAs($user)
        ->put('/settings/profile', ['name' => 'New Name'])
        ->assertRedirect(route('settings', ['section' => 'profile']))
        ->assertSessionHas('status');

    expect($user->fresh()->name)->toBe('New Name');
});

it('requires a name for profile update', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/profile', ['name' => ''])
        ->assertSessionHasErrors('name');
});

it('enforces name max length of 255', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/profile', ['name' => str_repeat('a', 256)])
        ->assertSessionHasErrors('name');
});

it('updates bio, location, timezone, and looking for', function () {
    Queue::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/profile', [
            'name' => $user->name,
            'bio' => 'My new bio text',
            'location' => 'Berlin, Germany',
            'timezone' => 'Europe/Berlin',
            'looking_for' => ['making friends', 'networking'],
        ])
        ->assertRedirect(route('settings', ['section' => 'profile']))
        ->assertSessionHas('status');

    $user->refresh();

    expect($user->bio)->toBe('My new bio text')
        ->and($user->location)->toBe('Berlin, Germany')
        ->and($user->timezone)->toBe('Europe/Berlin')
        ->and($user->looking_for)->toBe(['making friends', 'networking']);
});

it('uploads a valid avatar file', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200)->size(1024);

    $this->actingAs($user)
        ->put('/settings/profile', [
            'name' => $user->name,
            'avatar' => $file,
        ])
        ->assertRedirect(route('settings', ['section' => 'profile']))
        ->assertSessionHas('status');

    expect($user->fresh()->getFirstMedia('avatar'))->not->toBeNull();
});

it('rejects oversized avatar with 422', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200)->size(3000);

    $this->actingAs($user)
        ->put('/settings/profile', [
            'name' => $user->name,
            'avatar' => $file,
        ])
        ->assertSessionHasErrors('avatar');
});

it('rejects non-image avatar with 422', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    $this->actingAs($user)
        ->put('/settings/profile', [
            'name' => $user->name,
            'avatar' => $file,
        ])
        ->assertSessionHasErrors('avatar');
});

it('dispatches geocoding job when location changes', function () {
    $user = User::factory()->create(['location' => 'Old City']);

    Queue::fake();

    $this->actingAs($user)
        ->put('/settings/profile', [
            'name' => $user->name,
            'location' => 'New York, NY',
        ])
        ->assertRedirect(route('settings', ['section' => 'profile']));

    Queue::assertPushed(GeocodeLocation::class, function (GeocodeLocation $job) use ($user) {
        return $job->model->is($user)
            && $job->addressField === 'location'
            && $job->latitudeField === 'latitude'
            && $job->longitudeField === 'longitude';
    });
});

it('does not dispatch geocoding job when location is unchanged', function () {
    $user = User::factory()->create(['location' => 'Berlin, Germany']);

    Queue::fake();

    $this->actingAs($user)
        ->put('/settings/profile', [
            'name' => $user->name,
            'location' => 'Berlin, Germany',
        ])
        ->assertRedirect(route('settings', ['section' => 'profile']));

    Queue::assertNotPushed(GeocodeLocation::class);
});

it('saves interests as tags', function () {
    Queue::fake();
    Tag::findOrCreate('Photography', 'interest');
    Tag::findOrCreate('Hiking', 'interest');
    Tag::findOrCreate('Cooking', 'interest');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/profile', [
            'name' => $user->name,
            'interests' => ['Photography', 'Hiking'],
        ])
        ->assertRedirect(route('settings', ['section' => 'profile']));

    $interests = $user->fresh()->tagsWithType('interest')->pluck('name')->sort()->values()->toArray();
    expect($interests)->toBe(['Hiking', 'Photography']);
});

it('rejects invalid looking for option', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/profile', [
            'name' => $user->name,
            'looking_for' => ['invalid option'],
        ])
        ->assertSessionHasErrors('looking_for.0');
});

it('updates the user email and triggers re-verification', function () {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->put('/settings/account', ['email' => 'new@example.com'])
        ->assertRedirect(route('settings', ['section' => 'account']))
        ->assertSessionHas('status');

    $user->refresh();

    expect($user->email)->toBe('new@example.com')
        ->and($user->email_verified_at)->toBeNull();
});

it('requires a valid email for account update', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/account', ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');
});

it('requires a unique email for account update', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/account', ['email' => 'taken@example.com'])
        ->assertSessionHasErrors('email');
});

it('allows keeping the same email', function () {
    $user = User::factory()->create(['email' => 'same@example.com']);

    $this->actingAs($user)
        ->put('/settings/account', ['email' => 'same@example.com'])
        ->assertRedirect(route('settings', ['section' => 'account']));
});

it('updates the password with correct current password', function () {
    $user = User::factory()->create(['password' => 'oldpassword123']);

    $this->actingAs($user)
        ->put('/settings/account', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertRedirect(route('settings', ['section' => 'account']))
        ->assertSessionHas('status');

    expect(Hash::check('newpassword123', $user->fresh()->password))->toBeTrue();
});

it('requires current password to change password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/account', [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertSessionHasErrors('current_password');
});

it('rejects incorrect current password', function () {
    $user = User::factory()->create(['password' => 'correctpassword']);

    $this->actingAs($user)
        ->put('/settings/account', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertSessionHasErrors('current_password');
});

it('requires password confirmation to match', function () {
    $user = User::factory()->create(['password' => 'oldpassword123']);

    $this->actingAs($user)
        ->put('/settings/account', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ])
        ->assertSessionHasErrors('password');
});

it('requires password to be at least 8 characters', function () {
    $user = User::factory()->create(['password' => 'oldpassword123']);

    $this->actingAs($user)
        ->put('/settings/account', [
            'current_password' => 'oldpassword123',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
        ->assertSessionHasErrors('password');
});

it('requires authentication to access settings', function () {
    $this->get('/settings')
        ->assertRedirect('/login');
});
