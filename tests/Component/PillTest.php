<?php

it('renders with tag object', function () {
    $view = $this->blade('<x-pill :tag="$tag" />', ['tag' => (object) ['id' => 1, 'name' => 'Photography']]);

    $view->assertSee('Photography', false);
    $view->assertSee('bg-coral-50', false);
    $view->assertSee('text-coral-900', false);
});

it('renders with standalone name and id props', function () {
    $view = $this->blade('<x-pill name="Hiking" :id="2" />');

    $view->assertSee('Hiking', false);
    $view->assertSee('bg-violet-50', false);
    $view->assertSee('text-violet-900', false);
});

it('cycles to green for id % 4 == 0', function () {
    $view = $this->blade('<x-pill name="Cooking" :id="0" />');

    $view->assertSee('bg-green-50', false);
    $view->assertSee('text-green-700', false);
});

it('cycles to coral for id % 4 == 1', function () {
    $view = $this->blade('<x-pill name="Music" :id="1" />');

    $view->assertSee('bg-coral-50', false);
    $view->assertSee('text-coral-900', false);
});

it('cycles to violet for id % 4 == 2', function () {
    $view = $this->blade('<x-pill name="Art" :id="2" />');

    $view->assertSee('bg-violet-50', false);
    $view->assertSee('text-violet-900', false);
});

it('cycles to gold for id % 4 == 3', function () {
    $view = $this->blade('<x-pill name="Sports" :id="3" />');

    $view->assertSee('bg-gold-50', false);
    $view->assertSee('text-gold-900', false);
});

it('applies pill border radius', function () {
    $view = $this->blade('<x-pill name="Test" :id="0" />');

    $view->assertSee('rounded-pill', false);
});

it('cycles correctly for higher ids', function () {
    $view = $this->blade('<x-pill name="Gaming" :id="7" />');

    // 7 % 4 == 3 → gold
    $view->assertSee('bg-gold-50', false);
    $view->assertSee('text-gold-900', false);
});
