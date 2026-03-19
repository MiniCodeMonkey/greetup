<?php

it('renders cloud shape SVG with correct color, size, and opacity', function () {
    $view = $this->blade('<x-blob color="#FF6B4A" size="130" opacity="0.15" />');

    $view->assertSee('width="130"', false);
    $view->assertSee('height="130"', false);
    $view->assertSee('style="opacity: 0.15;"', false);
    $view->assertSee('fill="#FF6B4A"', false);
    $view->assertSee('M40 5 C55 5', false);
    $view->assertSee('viewBox="0 0 80 80"', false);
});

it('renders circle shape when shape is circle', function () {
    $view = $this->blade('<x-blob shape="circle" color="#FFB938" size="100" opacity="0.05" />');

    $view->assertSee('<circle cx="40" cy="40" r="38"', false);
    $view->assertSee('fill="#FFB938"', false);
    $view->assertDontSee('<path', false);
});

it('applies additional CSS classes from attributes', function () {
    $view = $this->blade('<x-blob class="-top-10 -right-8" />');

    $view->assertSee('absolute pointer-events-none -top-10 -right-8', false);
});

it('sets aria-hidden true for accessibility', function () {
    $view = $this->blade('<x-blob />');

    $view->assertSee('aria-hidden="true"', false);
});

it('uses default values when no props are provided', function () {
    $view = $this->blade('<x-blob />');

    $view->assertSee('width="200"', false);
    $view->assertSee('height="200"', false);
    $view->assertSee('style="opacity: 0.1;"', false);
    $view->assertSee('fill="#1FAF63"', false);
    $view->assertSee('M40 5 C55 5', false);
});
