<?php

declare(strict_types=1);

it('enables the webmcp search tool in the page config by default', function (): void {
    $this->get('/about')
        ->assertStatus(200)
        ->assertSee('webMcp: true', false);
});

it('disables the webmcp search tool when configured off', function (): void {
    config()->set('pergament.search.webmcp', false);

    $this->get('/about')
        ->assertStatus(200)
        ->assertSee('webMcp: false', false);
});

it('disables the webmcp search tool when search is disabled', function (): void {
    config()->set('pergament.search.enabled', false);

    $this->get('/about')
        ->assertStatus(200)
        ->assertSee('webMcp: false', false);
});
