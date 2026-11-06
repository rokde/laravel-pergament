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

it('serves the webmcp discovery manifest', function (): void {
    $response = $this->get('/.well-known/webmcp')
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'application/json; charset=UTF-8');

    $manifest = $response->json();

    expect($manifest['name'])->toBe(config('pergament.site.name'))
        ->and($manifest['tools'])->toHaveCount(1)
        ->and($manifest['tools'][0]['name'])->toBe('search')
        ->and($manifest['tools'][0]['url'])->toBe(route('pergament.search'))
        ->and($manifest['tools'][0]['inputSchema']['required'])->toBe(['query']);
});

it('answers preflight requests for the webmcp manifest', function (): void {
    $this->call('OPTIONS', '/.well-known/webmcp')->assertSuccessful();
});

it('marks the header search form as a declarative webmcp tool', function (): void {
    $this->get('/about')
        ->assertStatus(200)
        ->assertSee('toolname="search"', false)
        ->assertSee('tooldescription="', false)
        ->assertSee('toolparamdescription="Text to search for"', false);
});

it('omits declarative webmcp attributes when disabled', function (): void {
    config()->set('pergament.search.webmcp', false);

    $this->get('/about')
        ->assertStatus(200)
        ->assertDontSee('toolname=', false);
});

it('renders schema.org json-ld in the page head', function (): void {
    $html = $this->get('/about')->assertStatus(200)->getContent();

    preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);

    expect($matches)->toHaveCount(2);

    $data = json_decode($matches[1], true);

    expect($data['@context'])->toBe('https://schema.org')
        ->and($data['@graph'][0]['@type'])->toBe('WebSite')
        ->and($data['@graph'][1]['@type'])->toBe('WebPage');
});
