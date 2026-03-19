<?php

it('renders title and description', function () {
    $view = $this->blade('<x-empty-state title="No events yet" description="Create your first event to get started." />');

    $view->assertSee('No events yet', false);
    $view->assertSee('Create your first event to get started.', false);
});

it('renders decorative blob behind the text', function () {
    $view = $this->blade('<x-empty-state title="No events" description="Nothing here." />');

    $view->assertSee('aria-hidden="true"', false);
    $view->assertSee('opacity: 0.08', false);
    $view->assertSee('<svg', false);
});

it('renders centered layout', function () {
    $view = $this->blade('<x-empty-state title="Empty" description="Nothing." />');

    $view->assertSee('items-center', false);
    $view->assertSee('justify-center', false);
    $view->assertSee('text-center', false);
});

it('renders optional action slot when provided', function () {
    $view = $this->blade('
        <x-empty-state title="No members" description="Invite people to join.">
            <x-slot:action>
                <button class="btn-primary">Invite Members</button>
            </x-slot:action>
        </x-empty-state>
    ');

    $view->assertSee('Invite Members', false);
    $view->assertSee('btn-primary', false);
});

it('does not render action container when action slot is not provided', function () {
    $view = $this->blade('<x-empty-state title="No discussions" description="Start a new discussion." />');

    $view->assertSee('No discussions', false);
    $view->assertDontSee('btn-primary', false);
});
