<?php

it('renders up to max avatars with overlap margins', function () {
    $users = collect([
        (object) ['id' => 1, 'name' => 'Alice Smith'],
        (object) ['id' => 2, 'name' => 'Bob Jones'],
        (object) ['id' => 3, 'name' => 'Carol White'],
    ]);

    $view = $this->blade('<x-avatar-stack :users="$users" :max="5" />', ['users' => $users]);

    $view->assertSee('AS', false);
    $view->assertSee('BJ', false);
    $view->assertSee('CW', false);
    $view->assertSee('margin-left: -6px', false);
});

it('shows +N badge when users exceed max', function () {
    $users = collect([
        (object) ['id' => 1, 'name' => 'User One'],
        (object) ['id' => 2, 'name' => 'User Two'],
        (object) ['id' => 3, 'name' => 'User Three'],
        (object) ['id' => 4, 'name' => 'User Four'],
        (object) ['id' => 5, 'name' => 'User Five'],
    ]);

    $view = $this->blade('<x-avatar-stack :users="$users" :max="3" />', ['users' => $users]);

    $view->assertSee('+2', false);
    $view->assertSee('bg-neutral-100', false);
    $view->assertSee('text-neutral-500', false);
});

it('does not show +N badge when users equal max', function () {
    $users = collect([
        (object) ['id' => 1, 'name' => 'User One'],
        (object) ['id' => 2, 'name' => 'User Two'],
        (object) ['id' => 3, 'name' => 'User Three'],
    ]);

    $view = $this->blade('<x-avatar-stack :users="$users" :max="3" />', ['users' => $users]);

    $view->assertDontSee('+', false);
});

it('renders white border ring on avatars', function () {
    $users = collect([
        (object) ['id' => 1, 'name' => 'Jane Doe'],
    ]);

    $view = $this->blade('<x-avatar-stack :users="$users" />', ['users' => $users]);

    $view->assertSee('border: 2px solid white', false);
});

it('renders nothing when users collection is empty', function () {
    $users = collect([]);

    $view = $this->blade('<x-avatar-stack :users="$users" />', ['users' => $users]);

    $view->assertDontSee('flex', false);
    $view->assertDontSee('avatar', false);
});

it('defaults to max of 5', function () {
    $users = collect([
        (object) ['id' => 1, 'name' => 'User One'],
        (object) ['id' => 2, 'name' => 'User Two'],
        (object) ['id' => 3, 'name' => 'User Three'],
        (object) ['id' => 4, 'name' => 'User Four'],
        (object) ['id' => 5, 'name' => 'User Five'],
        (object) ['id' => 6, 'name' => 'User Six'],
        (object) ['id' => 7, 'name' => 'User Seven'],
        (object) ['id' => 8, 'name' => 'User Eight'],
    ]);

    $view = $this->blade('<x-avatar-stack :users="$users" />', ['users' => $users]);

    $view->assertSee('+3', false);
});

it('first avatar has no negative margin', function () {
    $users = collect([
        (object) ['id' => 1, 'name' => 'Solo User'],
    ]);

    $view = $this->blade('<x-avatar-stack :users="$users" />', ['users' => $users]);

    $view->assertSee('border: 2px solid white', false);
    $view->assertDontSee('margin-left: -6px', false);
});

it('renders white border ring on +N badge', function () {
    $users = collect([
        (object) ['id' => 1, 'name' => 'User One'],
        (object) ['id' => 2, 'name' => 'User Two'],
        (object) ['id' => 3, 'name' => 'User Three'],
    ]);

    $view = $this->blade('<x-avatar-stack :users="$users" :max="2" />', ['users' => $users]);

    $view->assertSee('+1', false);
    $view->assertSee('border: 2px solid white', false);
});
