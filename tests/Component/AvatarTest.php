<?php

it('renders sm size with correct pixel dimensions', function () {
    $user = (object) ['id' => 1, 'name' => 'Jane Doe'];
    $view = $this->blade('<x-avatar :user="$user" size="sm" />', ['user' => $user]);

    $view->assertSee('width: 24px', false);
    $view->assertSee('height: 24px', false);
});

it('renders md size by default with correct pixel dimensions', function () {
    $user = (object) ['id' => 1, 'name' => 'Jane Doe'];
    $view = $this->blade('<x-avatar :user="$user" />', ['user' => $user]);

    $view->assertSee('width: 32px', false);
    $view->assertSee('height: 32px', false);
});

it('renders lg size with correct pixel dimensions', function () {
    $user = (object) ['id' => 1, 'name' => 'Jane Doe'];
    $view = $this->blade('<x-avatar :user="$user" size="lg" />', ['user' => $user]);

    $view->assertSee('width: 44px', false);
    $view->assertSee('height: 44px', false);
});

it('renders xl size with correct pixel dimensions', function () {
    $user = (object) ['id' => 1, 'name' => 'Jane Doe'];
    $view = $this->blade('<x-avatar :user="$user" size="xl" />', ['user' => $user]);

    $view->assertSee('width: 96px', false);
    $view->assertSee('height: 96px', false);
});

it('cycles background color deterministically based on user id', function () {
    $user0 = (object) ['id' => 0, 'name' => 'User Zero'];
    $user1 = (object) ['id' => 1, 'name' => 'User One'];
    $user2 = (object) ['id' => 2, 'name' => 'User Two'];
    $user3 = (object) ['id' => 3, 'name' => 'User Three'];

    $view0 = $this->blade('<x-avatar :user="$user" />', ['user' => $user0]);
    $view1 = $this->blade('<x-avatar :user="$user" />', ['user' => $user1]);
    $view2 = $this->blade('<x-avatar :user="$user" />', ['user' => $user2]);
    $view3 = $this->blade('<x-avatar :user="$user" />', ['user' => $user3]);

    $view0->assertSee('bg-green-500', false);
    $view1->assertSee('bg-coral-500', false);
    $view2->assertSee('bg-violet-500', false);
    $view3->assertSee('bg-gold-500', false);
});

it('uses dark text for gold background and white text for others', function () {
    $goldUser = (object) ['id' => 3, 'name' => 'Gold User'];
    $greenUser = (object) ['id' => 0, 'name' => 'Green User'];

    $goldView = $this->blade('<x-avatar :user="$user" />', ['user' => $goldUser]);
    $greenView = $this->blade('<x-avatar :user="$user" />', ['user' => $greenUser]);

    $goldView->assertSee('text-neutral-900', false);
    $greenView->assertSee('text-white', false);
});

it('renders two initials for first and last name', function () {
    $user = (object) ['id' => 1, 'name' => 'Jane Doe'];
    $view = $this->blade('<x-avatar :user="$user" />', ['user' => $user]);

    $view->assertSee('JD', false);
});

it('falls back to single initial when user has one name', function () {
    $user = (object) ['id' => 1, 'name' => 'Madonna'];
    $view = $this->blade('<x-avatar :user="$user" />', ['user' => $user]);

    $view->assertSee('M', false);
    $view->assertDontSee('M ', false);
});

it('uses first and last name initials for multi-word names', function () {
    $user = (object) ['id' => 2, 'name' => 'Mary Jane Watson'];
    $view = $this->blade('<x-avatar :user="$user" />', ['user' => $user]);

    $view->assertSee('MW', false);
});

it('is fully rounded with radius-pill', function () {
    $user = (object) ['id' => 1, 'name' => 'Test User'];
    $view = $this->blade('<x-avatar :user="$user" />', ['user' => $user]);

    $view->assertSee('rounded-pill', false);
});
