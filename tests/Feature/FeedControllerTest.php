<?php

declare(strict_types=1);

it('serves the rss feed when feed type is set to rss', function (): void {
    config()->set('pergament.blog.feed.type', 'rss');

    $this->get('/blog/feed')
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
});

it('rss feed body contains rss root element', function (): void {
    config()->set('pergament.blog.feed.type', 'rss');

    $response = $this->get('/blog/feed');

    $response->assertStatus(200);
    expect($response->getContent())->toContain('<rss');
});

it('rss feed body contains channel element', function (): void {
    config()->set('pergament.blog.feed.type', 'rss');

    $response = $this->get('/blog/feed');

    $response->assertStatus(200);
    expect($response->getContent())->toContain('<channel>');
});
