<?php

declare(strict_types=1);

namespace Pergament\Tests;

use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Pergament\PergamentServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        TestResponse::macro('assertHeaderCaseInsensitive', function (string $headerName, string $value): static {
            /** @var TestResponse $this */
            expect(strtolower((string) $this->headers->get($headerName)))->toBe(strtolower($value));

            return $this;
        });
    }

    protected function getPackageProviders($app): array
    {
        return [PergamentServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('pergament.content_path', __DIR__.'/fixtures/content');
        $app['config']->set('pergament.site.name', 'Test Site');
        $app['config']->set('pergament.site.url', 'http://localhost');
        $app['config']->set('pergament.site.seo.title', 'Test Site');
        $app['config']->set('pergament.docs.enabled', true);
        $app['config']->set('pergament.blog.enabled', true);
        $app['config']->set('pergament.blog.feed.enabled', true);
        $app['config']->set('pergament.sitemap.enabled', true);
        $app['config']->set('pergament.search.enabled', true);
        $app['config']->set('pergament.pages.enabled', true);
        $app['config']->set('pergament.pwa.enabled', true);
    }
}
