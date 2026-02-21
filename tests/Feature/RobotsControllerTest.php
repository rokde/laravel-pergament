<?php

declare(strict_types=1);

it('returns custom robots.txt content when pergament.robots.content is configured', function (): void {
    config()->set('pergament.robots.content', 'User-agent: *\nDisallow: /admin');

    $this->get('/robots.txt')
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertContent('User-agent: *\nDisallow: /admin');
});

it('omits the Sitemap line from robots.txt when sitemap is disabled', function (): void {
    config()->set('pergament.sitemap.enabled', false);

    $response = $this->get('/robots.txt')->assertStatus(200);

    expect($response->getContent())->not->toContain('Sitemap:');
});

it('returns custom llms.txt content when pergament.llms.content is configured', function (): void {
    config()->set('pergament.llms.content', 'Custom LLM instructions here.');

    $this->get('/llms.txt')
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertContent('Custom LLM instructions here.');
});

it('includes the site description with > prefix in llms.txt when description is set', function (): void {
    config()->set('pergament.site.seo.description', 'A great documentation site.');

    $response = $this->get('/llms.txt')->assertStatus(200);

    expect($response->getContent())->toContain('> A great documentation site.');
});
