<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->withoutVite();

    Route::get('/test-error/{code}', fn (int $code) => abort($code));
});

it('renders 403 error page with correct content', function () {
    $response = $this->get('/test-error/403');

    $response->assertForbidden()
        ->assertSeeText('403')
        ->assertSeeText("You don't have access to this page")
        ->assertSeeText('You might need to join this group or have a different role to view this content.')
        ->assertSee('href="/explore"', false)
        ->assertSeeText('Go to Explore');
});

it('renders 404 error page with correct content', function () {
    $response = $this->get('/non-existent-page');

    $response->assertNotFound()
        ->assertSeeText('404')
        ->assertSeeText("We couldn't find that page")
        ->assertSeeText("The page you're looking for might have been moved, deleted, or never existed.")
        ->assertSee('href="/explore"', false)
        ->assertSeeText('Go to Explore');
});

it('renders 419 error page with correct content', function () {
    $response = $this->get('/test-error/419');

    $response->assertStatus(419)
        ->assertSeeText('419')
        ->assertSeeText('This page has expired')
        ->assertSeeText('Your session timed out. Please go back and try again.')
        ->assertSee('javascript:history.back()', false)
        ->assertSeeText('Go back');
});

it('renders 429 error page with correct content', function () {
    $response = $this->get('/test-error/429');

    $response->assertStatus(429)
        ->assertSeeText('429')
        ->assertSeeText('Slow down')
        ->assertSeeText("You're making requests too quickly. Please wait a moment and try again.")
        ->assertSee('href="/explore"', false)
        ->assertSeeText('Go to Explore');
});

it('renders 500 error page with correct content', function () {
    $response = $this->get('/test-error/500');

    $response->assertStatus(500)
        ->assertSeeText('500')
        ->assertSeeText('Something went wrong')
        ->assertSeeText('We hit an unexpected error. If this keeps happening, please let the site administrator know.')
        ->assertSee('href="/"', false)
        ->assertSeeText('Go to homepage');
});

it('renders 503 error page with correct content', function () {
    $response = $this->get('/test-error/503');

    $response->assertStatus(503)
        ->assertSeeText('503')
        ->assertSeeText("We'll be right back")
        ->assertSeeText('Greetup is undergoing maintenance. Please check back shortly.')
        ->assertSee('meta http-equiv="refresh" content="60"', false);
});

it('error pages include navbar', function () {
    $response = $this->get('/non-existent-page');

    $response->assertNotFound()
        ->assertSee('greetup.png');
});

it('error pages include decorative blob', function () {
    $response = $this->get('/non-existent-page');

    $response->assertNotFound()
        ->assertSee('opacity', false);
});
