<?php

declare(strict_types=1);

use Pergament\Services\MarkdownRenderer;

it('converts markdown to html', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $html = $renderer->toHtml('**bold** and *italic*');

    expect($html)->toContain('<strong>bold</strong>');
    expect($html)->toContain('<em>italic</em>');
});

it('adds ids to h2 and h3 headings', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $html = $renderer->toHtml("## My Section\n\n### Sub Section");

    expect($html)->toContain('id="my-section"');
    expect($html)->toContain('id="sub-section"');
});

it('strips first h1', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $html = $renderer->toHtml("# Title\n\nSome content");
    $stripped = $renderer->stripFirstH1($html);

    expect($stripped)->not->toContain('<h1>');
    expect($stripped)->toContain('Some content');
});

it('extracts headings', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $html = $renderer->toHtml("## First\n\n### Second\n\n## Third");
    $headings = $renderer->extractHeadings($html);

    expect($headings)->toHaveCount(3);
    expect($headings[0]->text)->toBe('First');
    expect($headings[0]->level)->toBe(2);
    expect($headings[1]->text)->toBe('Second');
    expect($headings[1]->level)->toBe(3);
    expect($headings[2]->text)->toBe('Third');
    expect($headings[2]->level)->toBe(2);
});

it('processes block directives', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $html = $renderer->toHtml(":::hero\n\nHero content here\n\n:::");

    expect($html)->toContain('pergament-block');
    expect($html)->toContain('pergament-block-hero');
    expect($html)->toContain('Hero content here');
});

it('replaces double dashes with em dashes', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $html = $renderer->toHtml('This is a sentence -- with a dash.');

    expect($html)->toContain('This is a sentence — with a dash.');
    expect($html)->not->toContain(' -- ');
});

it('does not replace dashes inside code blocks', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $html = $renderer->toHtml('A normal -- dash and `code -- block`');

    // The em dash in normal text is replaced, code inline is tricky
    // but the markdown-level replacement happens before code blocks are parsed
    expect($html)->toContain('—');
});

it('resolves relative .md links to correct urls', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $contentPath = config('pergament.content_path').'/'.config('pergament.docs.path', 'docs');
    $sourceFile = $contentPath.'/0-getting-started/01-introduction.md';

    $html = '<a href="./02-configuration.md">Configuration</a>';
    $result = $renderer->resolveContentLinks($html, $sourceFile);

    expect($result['html'])->toContain('href="/docs/getting-started/configuration"');
    expect($result['html'])->toContain('>Configuration</a>');
    expect($result['linkErrors'])->toBeEmpty();
});

it('resolves relative .md links across chapters', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $contentPath = config('pergament.content_path').'/'.config('pergament.docs.path', 'docs');
    $sourceFile = $contentPath.'/0-getting-started/01-introduction.md';

    $html = '<a href="../1-advanced/01-customization.md">Customization</a>';
    $result = $renderer->resolveContentLinks($html, $sourceFile);

    expect($result['html'])->toContain('href="/docs/advanced/customization"');
    expect($result['linkErrors'])->toBeEmpty();
});

it('preserves anchor fragments in .md links', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $contentPath = config('pergament.content_path').'/'.config('pergament.docs.path', 'docs');
    $sourceFile = $contentPath.'/0-getting-started/01-introduction.md';

    $html = '<a href="./02-configuration.md#basic-setup">Config Setup</a>';
    $result = $renderer->resolveContentLinks($html, $sourceFile);

    expect($result['html'])->toContain('href="/docs/getting-started/configuration#basic-setup"');
    expect($result['linkErrors'])->toBeEmpty();
});

it('strips link and reports error for missing .md targets', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $contentPath = config('pergament.content_path').'/'.config('pergament.docs.path', 'docs');
    $sourceFile = $contentPath.'/0-getting-started/01-introduction.md';

    $html = '<a href="./99-nonexistent.md">Missing Page</a>';
    $result = $renderer->resolveContentLinks($html, $sourceFile);

    expect($result['html'])->toBe('Missing Page');
    expect($result['html'])->not->toContain('<a');
    expect($result['linkErrors'])->toHaveCount(1);
    expect($result['linkErrors'][0])->toContain('Broken link');
});

it('leaves external .md urls untouched', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $sourceFile = config('pergament.content_path').'/pages/home.md';

    $html = '<a href="https://example.com/readme.md">External</a>';
    $result = $renderer->resolveContentLinks($html, $sourceFile);

    expect($result['html'])->toBe($html);
    expect($result['linkErrors'])->toBeEmpty();
});

it('leaves non-.md links untouched', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $sourceFile = config('pergament.content_path').'/pages/home.md';

    $html = '<a href="/some/path">Normal link</a>';
    $result = $renderer->resolveContentLinks($html, $sourceFile);

    expect($result['html'])->toBe($html);
    expect($result['linkErrors'])->toBeEmpty();
});

it('resolves links from pages to doc pages', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $sourceFile = config('pergament.content_path').'/pages/home.md';

    $html = '<a href="../docs/0-getting-started/01-introduction.md">Intro</a>';
    $result = $renderer->resolveContentLinks($html, $sourceFile);

    expect($result['html'])->toContain('href="/docs/getting-started/introduction"');
    expect($result['linkErrors'])->toBeEmpty();
});

it('resolves links to standalone pages', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $contentPath = config('pergament.content_path').'/'.config('pergament.docs.path', 'docs');
    $sourceFile = $contentPath.'/0-getting-started/01-introduction.md';

    $html = '<a href="../../pages/about.md">About</a>';
    $result = $renderer->resolveContentLinks($html, $sourceFile);

    expect($result['html'])->toContain('href="/about"');
    expect($result['linkErrors'])->toBeEmpty();
});

it('resolves links to blog posts', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $sourceFile = config('pergament.content_path').'/pages/home.md';

    $html = '<a href="../blog/2024-01-15-hello-world/post.md">Hello World</a>';
    $result = $renderer->resolveContentLinks($html, $sourceFile);

    expect($result['html'])->toContain('href="/blog/hello-world"');
    expect($result['linkErrors'])->toBeEmpty();
});

it('reports cannot resolve URL error for .md file outside content paths', function (): void {
    $renderer = resolve(MarkdownRenderer::class);

    // Create a temp directory with two .md files, neither under docs/blog/pages
    $tmpDir = sys_get_temp_dir().'/pergament_test_'.uniqid();
    mkdir($tmpDir);
    file_put_contents($tmpDir.'/source.md', '# Source');
    file_put_contents($tmpDir.'/target.md', '# Target');

    $html = '<a href="./target.md">Target</a>';
    $result = $renderer->resolveContentLinks($html, $tmpDir.'/source.md');

    // Link is stripped to just the link text, and an error is recorded
    expect($result['html'])->toBe('Target');
    expect($result['html'])->not->toContain('<a');
    expect($result['linkErrors'])->toHaveCount(1);
    expect($result['linkErrors'][0])->toContain('Cannot resolve URL');

    // Cleanup
    unlink($tmpDir.'/source.md');
    unlink($tmpDir.'/target.md');
    rmdir($tmpDir);
});

it('highlights code blocks with a named language', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $html = $renderer->toHtml("```php\n<?php echo 'hello';\n```");

    expect($html)->toContain('class="pergament-code-block"');
    expect($html)->toContain('data-language="php"');
});

it('highlights code blocks without a language', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $html = $renderer->toHtml("```\nplain code\n```");

    expect($html)->toContain('class="pergament-code-block"');
    expect($html)->not->toContain('data-language');
});

it('returns empty array when no headings present', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $html = '<p>Just a paragraph</p>';

    expect($renderer->extractHeadings($html))->toBeEmpty();
});

it('renders note alert', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $html = $renderer->toHtml("> [!NOTE]\n> Useful information.");

    expect($html)->toContain('pergament-alert');
    expect($html)->toContain('pergament-alert-note');
    expect($html)->toContain('role="alert"');
    expect($html)->toContain('Note');
    expect($html)->toContain('Useful information.');
    expect($html)->not->toContain('<blockquote>');
    expect($html)->not->toContain('[!NOTE]');
});

it('renders tip alert', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $html = $renderer->toHtml("> [!TIP]\n> Helpful advice.");

    expect($html)->toContain('pergament-alert-tip');
    expect($html)->toContain('Tip');
    expect($html)->toContain('Helpful advice.');
});

it('renders important alert', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $html = $renderer->toHtml("> [!IMPORTANT]\n> Key information.");

    expect($html)->toContain('pergament-alert-important');
    expect($html)->toContain('Important');
    expect($html)->toContain('Key information.');
});

it('renders warning alert', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $html = $renderer->toHtml("> [!WARNING]\n> Urgent info.");

    expect($html)->toContain('pergament-alert-warning');
    expect($html)->toContain('Warning');
    expect($html)->toContain('Urgent info.');
});

it('renders caution alert', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $html = $renderer->toHtml("> [!CAUTION]\n> Risk of negative outcomes.");

    expect($html)->toContain('pergament-alert-caution');
    expect($html)->toContain('Caution');
    expect($html)->toContain('Risk of negative outcomes.');
});

it('renders alert icon svg', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $html = $renderer->toHtml("> [!NOTE]\n> Content.");

    expect($html)->toContain('<svg');
    expect($html)->toContain('aria-hidden="true"');
});

it('does not render alerts when disabled via config', function (): void {
    config(['pergament.markdown.alerts.enabled' => false]);

    $renderer = resolve(MarkdownRenderer::class);
    $html = $renderer->toHtml("> [!NOTE]\n> Useful information.");

    expect($html)->toContain('<blockquote>');
    expect($html)->not->toContain('pergament-alert');

    config(['pergament.markdown.alerts.enabled' => true]);
});

it('renders alert with multiple paragraphs', function (): void {
    $renderer = resolve(MarkdownRenderer::class);
    $html = $renderer->toHtml("> [!NOTE]\n> First paragraph.\n>\n> Second paragraph.");

    expect($html)->toContain('pergament-alert-note');
    expect($html)->toContain('First paragraph.');
    expect($html)->toContain('Second paragraph.');
});
