<?php

declare(strict_types=1);

it('returns html for a normal request without any markdown triggers', function (): void {
    $this->get('/about')
        ->assertStatus(200)
        ->assertHeaderCaseInsensitive('Content-Type', 'text/html; charset=UTF-8');
});

it('returns markdown content type when path ends with .md', function (): void {
    $this->get('/index.md')
        ->assertStatus(200)
        ->assertHeaderCaseInsensitive('Content-Type', 'text/markdown; charset=UTF-8');
});

it('returns markdown when Accept header contains text/markdown', function (): void {
    $this->withHeaders(['Accept' => 'text/markdown'])
        ->get('/about')
        ->assertStatus(200)
        ->assertHeaderCaseInsensitive('Content-Type', 'text/markdown; charset=UTF-8');
});

it('returns markdown when request comes from a known bot user-agent', function (): void {
    $this->withHeader('User-Agent', 'claudebot/1.0')
        ->get('/about')
        ->assertStatus(200)
        ->assertHeaderCaseInsensitive('Content-Type', 'text/markdown; charset=UTF-8');
});

it('includes a Content-Signal header in markdown responses when content_signals are configured', function (): void {
    $this->get('/index.md')
        ->assertStatus(200)
        ->assertHeaderCaseInsensitive('Content-Type', 'text/markdown; charset=UTF-8')
        ->assertHeader('Content-Signal', 'ai-train=disallow, ai-input=allow, search=allow');
});

it('does not include a Content-Signal header when content_signals config is empty', function (): void {
    config()->set('pergament.exports.markdown.content_signals', []);

    $this->get('/index.md')
        ->assertStatus(200)
        ->assertHeaderCaseInsensitive('Content-Type', 'text/markdown; charset=UTF-8')
        ->assertHeaderMissing('Content-Signal');
});

it('includes an X-Markdown-Tokens header with a numeric value in markdown responses', function (): void {
    $response = $this->get('/index.md')
        ->assertStatus(200)
        ->assertHeaderCaseInsensitive('Content-Type', 'text/markdown; charset=UTF-8');

    $tokenHeader = $response->headers->get('X-Markdown-Tokens');
    expect($tokenHeader)->not->toBeNull();
    expect((int) $tokenHeader)->toBeGreaterThan(0);
});

it('includes X-Robots-Tag and Vary headers in markdown responses', function (): void {
    $this->get('/index.md')
        ->assertStatus(200)
        ->assertHeaderCaseInsensitive('Content-Type', 'text/markdown; charset=UTF-8')
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertHeader('Vary', 'Accept');
});

it('returns markdown content for a blog post when path ends with .md', function (): void {
    $this->get('/blog/hello-world.md')
        ->assertStatus(200)
        ->assertHeaderCaseInsensitive('Content-Type', 'text/markdown; charset=UTF-8');
});

it('returns raw markdown body for a page (not html-converted)', function (): void {
    $response = $this->get('/about.md')
        ->assertStatus(200)
        ->assertHeaderCaseInsensitive('Content-Type', 'text/markdown; charset=UTF-8');

    $body = $response->getContent();
    expect($body)->toContain('# About Us');
    expect($body)->toContain('We are a great team.');
    // Must not contain HTML tags
    expect($body)->not->toContain('<html');
    expect($body)->not->toContain('<nav');
    expect($body)->not->toContain('<header');
    expect($body)->not->toContain('<footer');
});

it('returns raw markdown body for a blog post (not html-converted)', function (): void {
    $response = $this->get('/blog/hello-world.md')
        ->assertStatus(200)
        ->assertHeaderCaseInsensitive('Content-Type', 'text/markdown; charset=UTF-8');

    $body = $response->getContent();
    expect($body)->toContain('# Hello World');
    expect($body)->toContain('Welcome to our blog!');
    // Must not contain HTML tags
    expect($body)->not->toContain('<html');
    expect($body)->not->toContain('<nav');
    expect($body)->not->toContain('<header');
});

it('returns raw markdown body for a doc page (not html-converted)', function (): void {
    $response = $this->get('/docs/getting-started/introduction.md')
        ->assertStatus(200)
        ->assertHeaderCaseInsensitive('Content-Type', 'text/markdown; charset=UTF-8');

    $body = $response->getContent();
    expect($body)->toContain('# Introduction');
    expect($body)->toContain('Welcome to the documentation.');
    // Must not contain HTML tags
    expect($body)->not->toContain('<html');
    expect($body)->not->toContain('<nav');
    expect($body)->not->toContain('<header');
});

it('raw markdown response still includes LLM headers (Content-Signal, X-Markdown-Tokens)', function (): void {
    $response = $this->get('/about.md')
        ->assertStatus(200)
        ->assertHeaderCaseInsensitive('Content-Type', 'text/markdown; charset=UTF-8')
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertHeader('Vary', 'Accept');

    $tokenHeader = $response->headers->get('X-Markdown-Tokens');
    expect($tokenHeader)->not->toBeNull();
    expect((int) $tokenHeader)->toBeGreaterThan(0);
});
