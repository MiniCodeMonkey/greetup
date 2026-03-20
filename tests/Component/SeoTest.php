<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::put(Setting::CACHE_KEY, Setting::DEFAULTS);
});

it('renders title tag', function () {
    $view = $this->blade('<x-seo title="My Event" description="A great event" />');

    $view->assertSee('<title>My Event</title>', false);
});

it('renders meta description', function () {
    $view = $this->blade('<x-seo title="My Event" description="A great event" />');

    $view->assertSee('<meta name="description" content="A great event">', false);
});

it('renders canonical url when provided', function () {
    $view = $this->blade('<x-seo title="My Event" description="A great event" canonicalUrl="https://example.com/events/1" />');

    $view->assertSee('<link rel="canonical" href="https://example.com/events/1">', false);
});

it('renders canonical url from current url when not explicitly provided', function () {
    $view = $this->blade('<x-seo title="My Event" description="A great event" />');

    $view->assertSee('rel="canonical"', false);
});

it('renders open graph tags', function () {
    $view = $this->blade('<x-seo title="My Event" description="A great event" canonicalUrl="https://example.com/events/1" />');

    $view->assertSee('<meta property="og:type" content="website">', false);
    $view->assertSee('<meta property="og:title" content="My Event">', false);
    $view->assertSee('<meta property="og:description" content="A great event">', false);
    $view->assertSee('<meta property="og:url" content="https://example.com/events/1">', false);
    $view->assertSee('og:site_name', false);
});

it('renders open graph with custom type', function () {
    $view = $this->blade('<x-seo title="My Event" description="A great event" type="article" />');

    $view->assertSee('<meta property="og:type" content="article">', false);
});

it('renders twitter card tags', function () {
    $view = $this->blade('<x-seo title="My Event" description="A great event" />');

    $view->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
    $view->assertSee('<meta name="twitter:title" content="My Event">', false);
    $view->assertSee('<meta name="twitter:description" content="A great event">', false);
    $view->assertSee('twitter:image', false);
});

it('uses page-specific image when provided', function () {
    $view = $this->blade('<x-seo title="My Event" description="A great event" image="https://example.com/cover.jpg" />');

    $view->assertSee('<meta property="og:image" content="https://example.com/cover.jpg">', false);
    $view->assertSee('<meta name="twitter:image" content="https://example.com/cover.jpg">', false);
});

it('falls back to default og image when no image provided', function () {
    $view = $this->blade('<x-seo title="My Event" description="A great event" />');

    $view->assertSee('og-default.png', false);
});

it('renders json-ld script block when provided', function () {
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        'name' => 'My Event',
    ];

    $view = $this->blade('<x-seo title="My Event" description="A great event" :jsonLd="$jsonLd" />', ['jsonLd' => $jsonLd]);

    $view->assertSee('<script type="application/ld+json">', false);
    $view->assertSee('"@context":"https://schema.org"', false);
    $view->assertSee('"@type":"Event"', false);
    $view->assertSee('"name":"My Event"', false);
});

it('does not render json-ld when not provided', function () {
    $view = $this->blade('<x-seo title="My Event" description="A great event" />');

    $view->assertDontSee('application/ld+json', false);
});

it('renders og:site_name from setting model', function () {
    $view = $this->blade('<x-seo title="My Event" description="A great event" />');

    $view->assertSee('<meta property="og:site_name" content="Greetup">', false);
});

it('renders og:url from current url when no canonical provided', function () {
    $view = $this->blade('<x-seo title="My Event" description="A great event" />');

    $view->assertSee('<meta property="og:url"', false);
});
