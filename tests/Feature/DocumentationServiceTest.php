<?php

declare(strict_types=1);

use Pergament\Data\DocChapter;
use Pergament\Data\DocPage;
use Pergament\Services\DocumentationService;

it('returns chapters ordered by directory prefix', function (): void {
    $service = resolve(DocumentationService::class);
    $chapters = $service->getChapters();

    expect($chapters)->toHaveCount(2);
    expect($chapters->first())->toBeInstanceOf(DocChapter::class);
    expect($chapters->first()->slug)->toBe('getting-started');
    expect($chapters->last()->slug)->toBe('advanced');
});

it('returns navigation structure', function (): void {
    $service = resolve(DocumentationService::class);
    $navigation = $service->getNavigation();

    expect($navigation)->toHaveCount(2);
    expect($navigation->first())->toHaveKeys(['title', 'slug', 'pages']);
    expect($navigation->first()['slug'])->toBe('getting-started');
    expect($navigation->first()['pages'])->toHaveCount(2);
    expect($navigation->first()['pages'][0])->toHaveKeys(['title', 'slug']);
});

it('returns a page by chapter and page slug', function (): void {
    $service = resolve(DocumentationService::class);
    $page = $service->getPage('getting-started', 'introduction');

    expect($page)->toBeInstanceOf(DocPage::class);
    expect($page->title)->toBe('Introduction');
    expect($page->excerpt)->toBe('Learn how to get started.');
    expect($page->slug)->toBe('introduction');
    expect($page->content)->toContain('Welcome to the documentation');
});

it('returns null for non-existent page', function (): void {
    $service = resolve(DocumentationService::class);

    expect($service->getPage('nonexistent', 'x'))->toBeNull();
});

it('renders a page with HTML content', function (): void {
    $service = resolve(DocumentationService::class);
    $rendered = $service->getRenderedPage('getting-started', 'introduction');

    expect($rendered)->not->toBeNull();
    expect($rendered)->toHaveKeys(['title', 'excerpt', 'htmlContent', 'headings', 'slug']);
    expect($rendered['htmlContent'])->not->toContain('<h1>');
    expect($rendered['htmlContent'])->toContain('id="getting-started"');
});

it('extracts headings from rendered page', function (): void {
    $service = resolve(DocumentationService::class);
    $rendered = $service->getRenderedPage('getting-started', 'introduction');

    expect($rendered['headings'])->toBeArray();
    expect($rendered['headings'])->not->toBeEmpty();

    $headingTexts = array_map(fn ($h) => $h->text, $rendered['headings']);
    expect($headingTexts)->toContain('Getting Started');
    expect($headingTexts)->toContain('Installation');
});

it('includes previous and next page links', function (): void {
    $service = resolve(DocumentationService::class);
    $rendered = $service->getRenderedPage('getting-started', 'configuration');

    expect($rendered['previousPage'])->not->toBeNull();
    expect($rendered['previousPage']['title'])->toBe('Introduction');
    expect($rendered['nextPage'])->not->toBeNull();
    expect($rendered['nextPage']['title'])->toBe('Customization');
});

it('detects themed image variants', function (): void {
    $fixturesPath = __DIR__.'/../fixtures/content/docs/0-getting-started';
    file_put_contents($fixturesPath.'/screenshot.dark.png', 'fake-dark-image');

    $service = resolve(DocumentationService::class);
    $variants = $service->resolveThemedImageVariants('getting-started', 'screenshot.png');

    expect($variants['hasDark'])->toBeTrue();
    expect($variants['hasLight'])->toBeFalse();

    @unlink($fixturesPath.'/screenshot.dark.png');
});

it('searches documentation', function (): void {
    $service = resolve(DocumentationService::class);
    $results = $service->search('installation');

    expect($results)->not->toBeEmpty();
    expect($results->first()['title'])->toBe('Introduction');
    expect($results->first()['type'])->toBe('doc');
});

it('returns first page', function (): void {
    $service = resolve(DocumentationService::class);
    $first = $service->getFirstPage();

    expect($first)->not->toBeNull();
    expect($first['chapter'])->toBe('getting-started');
    expect($first['page'])->toBe('introduction');
});

it('includes linkErrors key in rendered page', function (): void {
    $service = resolve(DocumentationService::class);
    $rendered = $service->getRenderedPage('getting-started', 'introduction');

    expect($rendered)->toHaveKey('linkErrors');
    expect($rendered['linkErrors'])->toBeArray();
});
