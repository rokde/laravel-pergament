<?php

declare(strict_types=1);

use Pergament\Services\PageService;

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir().'/pergament-test-'.uniqid();
    mkdir($this->tempDir, 0755, true);

    $this->outputDir = sys_get_temp_dir().'/pergament-static-'.uniqid();
});

afterEach(function (): void {
    foreach ([$this->tempDir, $this->outputDir] as $dir) {
        if (is_dir($dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
            }
            rmdir($dir);
        }
    }
});

it('generates homepage index.html', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    expect(file_exists($this->outputDir.'/index.html'))->toBeTrue();
    expect(file_get_contents($this->outputDir.'/index.html'))->toContain('Welcome');
});

it('copies the configured favicon into the static output root', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    expect(file_exists($this->outputDir.'/favicon.ico'))->toBeTrue();
    expect(file_get_contents($this->outputDir.'/favicon.ico'))
        ->toBe(file_get_contents(__DIR__.'/../fixtures/content/favicon.ico'));
});

it('preserves style tags in allow html pages during static generation', function (): void {
    mkdir($this->tempDir.'/pages', 0755, true);

    file_put_contents($this->tempDir.'/pages/home.md', <<<'MARKDOWN'
---
title: HTML Home
allow_html: true
---

# HTML Home

<style>
  .custom-home { color: red; }
</style>

<section class="custom-home">Styled content</section>
MARKDOWN);

    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
        '--content-path' => $this->tempDir,
    ])->assertSuccessful();

    expect(file_exists($this->outputDir.'/index.html'))->toBeTrue();

    $html = file_get_contents($this->outputDir.'/index.html');

    expect($html)->toContain('<style>')
        ->and($html)->toContain('.custom-home { color: red; }')
        ->and($html)->not->toContain('&lt;style>')
        ->and($html)->toContain('<section class="custom-home">Styled content</section>');
});

it('strips style, script and raw html tags from markdown sidecars', function (): void {
    mkdir($this->tempDir.'/pages', 0755, true);

    file_put_contents($this->tempDir.'/pages/home.md', <<<'MARKDOWN'
---
title: HTML Home
allow_html: true
---

# HTML Home

<style>
  .custom-home { color: red; }
</style>

<script>console.log('tracker');</script>

<section class="custom-home">Styled content</section>

Plain paragraph text.
MARKDOWN);

    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
        '--content-path' => $this->tempDir,
    ])->assertSuccessful();

    $md = $this->outputDir.'/index.md';
    expect(file_exists($md))->toBeTrue();

    $content = file_get_contents($md);

    expect($content)->toContain('# HTML Home')
        ->and($content)->toContain('Styled content')
        ->and($content)->toContain('Plain paragraph text.')
        ->and($content)->not->toContain('<style>')
        ->and($content)->not->toContain('.custom-home { color: red; }')
        ->and($content)->not->toContain('<script>')
        ->and($content)->not->toContain("console.log('tracker')")
        ->and($content)->not->toContain('<section');
});

it('generates doc pages as flat .html files', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $docsPrefix = config('pergament.docs.url_prefix', 'docs');

    // Doc index redirect
    expect(file_exists($this->outputDir.'/'.$docsPrefix.'/index.html'))->toBeTrue();
    expect(file_get_contents($this->outputDir.'/'.$docsPrefix.'/index.html'))->toContain('meta http-equiv="refresh"');

    // Doc pages as flat html
    expect(file_exists($this->outputDir.'/'.$docsPrefix.'/getting-started/introduction.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/'.$docsPrefix.'/getting-started/configuration.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/'.$docsPrefix.'/advanced/customization.html'))->toBeTrue();

    $content = file_get_contents($this->outputDir.'/'.$docsPrefix.'/getting-started/introduction.html');
    expect($content)->toContain('Introduction');
});

it('generates a markdown sidecar for each doc page', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $docsPrefix = config('pergament.docs.url_prefix', 'docs');

    $md = $this->outputDir.'/'.$docsPrefix.'/getting-started/introduction.md';
    expect(file_exists($md))->toBeTrue();
    expect(file_get_contents($md))->toContain('# Introduction');
});

it('generates blog index with .html pagination', function (): void {
    config()->set('pergament.blog.per_page', 1);

    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $blogPrefix = config('pergament.blog.url_prefix', 'blog');

    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/index.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/page/1.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/page/2.html'))->toBeTrue();
});

it('generates individual blog post files with markdown sidecars', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $blogPrefix = config('pergament.blog.url_prefix', 'blog');

    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/hello-world.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/hello-world.md'))->toBeTrue();
    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/getting-started-with-laravel.html'))->toBeTrue();

    $content = file_get_contents($this->outputDir.'/'.$blogPrefix.'/hello-world.html');
    expect($content)->toContain('Hello World');
});

it('generates category pages', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $blogPrefix = config('pergament.blog.url_prefix', 'blog');

    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/category/general.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/category/tutorials.html'))->toBeTrue();
});

it('generates tag pages', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $blogPrefix = config('pergament.blog.url_prefix', 'blog');

    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/tag/intro.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/tag/laravel.html'))->toBeTrue();
});

it('generates author pages', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $blogPrefix = config('pergament.blog.url_prefix', 'blog');

    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/author/jane-doe.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/author/john-smith.html'))->toBeTrue();
});

it('generates sitemap.xml', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    expect(file_exists($this->outputDir.'/sitemap.xml'))->toBeTrue();
    expect(file_get_contents($this->outputDir.'/sitemap.xml'))->toContain('<urlset');
});

it('generates robots.txt', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    expect(file_exists($this->outputDir.'/robots.txt'))->toBeTrue();
    expect(file_get_contents($this->outputDir.'/robots.txt'))->toContain('User-agent: *');
});

it('generates llms.txt', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    expect(file_exists($this->outputDir.'/llms.txt'))->toBeTrue();
    expect(file_get_contents($this->outputDir.'/llms.txt'))->toContain('# Test Site');
});

it('generates feed as blog/feed.xml', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $blogPrefix = config('pergament.blog.url_prefix', 'blog');

    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/feed.xml'))->toBeTrue();
    expect(file_get_contents($this->outputDir.'/'.$blogPrefix.'/feed.xml'))->toContain('<feed');
});

it('generates a client-side search index', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    expect(file_exists($this->outputDir.'/search.json'))->toBeTrue();

    $index = json_decode((string) file_get_contents($this->outputDir.'/search.json'), true);

    expect($index)->toBeArray()->not->toBeEmpty();

    $titles = array_column($index, 'title');
    expect($titles)->toContain('Introduction');

    $intro = collect($index)->firstWhere('title', 'Introduction');
    expect($intro)->toHaveKeys(['title', 'excerpt', 'content', 'url', 'type'])
        ->and($intro['type'])->toBe('doc')
        ->and($intro['url'])->toBe('docs/getting-started/introduction.html')
        ->and($intro['url'])->not->toStartWith('/')
        ->and($intro['content'])->toContain('documentation');
});

it('points search at a relative index and disables the service worker', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $docsPrefix = config('pergament.docs.url_prefix', 'docs');

    // Deep page: search index reached by walking up to the root.
    $deep = file_get_contents($this->outputDir.'/'.$docsPrefix.'/getting-started/introduction.html');
    expect($deep)->toMatch('/searchUrl:\s*"(?:\.\.\\\\\/\.\.\\\\\/search\.json|\.\.\/\.\.\/search\.json)"/')
        ->and($deep)->toContain('swUrl: null');

    // Root page: index sits right next to it.
    $home = file_get_contents($this->outputDir.'/index.html');
    expect($home)->toContain('searchUrl: "search.json"');
});

it('rewrites static search forms away from the live search route', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $docsPrefix = config('pergament.docs.url_prefix', 'docs');
    $deep = file_get_contents($this->outputDir.'/'.$docsPrefix.'/getting-started/introduction.html');

    expect($deep)->not->toContain('action="http://localhost/search"')
        ->and($deep)->not->toContain('action="/search"')
        ->and($deep)->toContain('data-pergament-static-search="true"');
});

it('omits the search index when search is disabled', function (): void {
    config()->set('pergament.search.enabled', false);

    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    expect(file_exists($this->outputDir.'/search.json'))->toBeFalse();
});

it('bundles css, js and fonts into a self-contained assets directory', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    expect(file_exists($this->outputDir.'/assets/pergament.css'))->toBeTrue();
    expect(file_exists($this->outputDir.'/assets/pergament.js'))->toBeTrue();
    expect(file_exists($this->outputDir.'/assets/fonts/OpenDyslexic-Regular.otf'))->toBeTrue();

    // Font URLs inside the stylesheet must be relative, not absolute.
    $css = file_get_contents($this->outputDir.'/assets/pergament.css');
    expect($css)->toContain('url(fonts/OpenDyslexic-Regular.otf)');
    expect($css)->not->toContain('/vendor/pergament/fonts/');
});

it('rewrites asset links to relative, version-free paths', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $home = file_get_contents($this->outputDir.'/index.html');

    expect($home)->toContain('href="assets/pergament.css"')
        ->and($home)->toContain('src="assets/pergament.js"')
        ->and($home)->not->toContain('pergament.css?v=')
        ->and($home)->not->toContain('/vendor/pergament/');
});

it('produces a self-contained homepage with no absolute internal links', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $home = file_get_contents($this->outputDir.'/index.html');

    expect($home)->not->toContain('href="http://localhost')
        ->and($home)->not->toContain('src="http://localhost');
});

it('links between doc pages with relative .html paths', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $docsPrefix = config('pergament.docs.url_prefix', 'docs');
    $intro = file_get_contents($this->outputDir.'/'.$docsPrefix.'/getting-started/introduction.html');

    // Sibling doc page is linked relatively with a .html extension.
    expect($intro)->toContain('configuration.html');
    // Assets are reached by walking up out of the chapter directory.
    expect($intro)->toContain('../../assets/pergament.css');
});

it('copies media files from doc content dirs', function (): void {
    $docsPath = config('pergament.content_path').'/'.config('pergament.docs.path', 'docs');
    $chapterDir = $docsPath.'/0-getting-started';
    file_put_contents($chapterDir.'/diagram.png', 'fake-image-data');

    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $docsPrefix = config('pergament.docs.url_prefix', 'docs');

    expect(file_exists($this->outputDir.'/'.$docsPrefix.'/media/getting-started/diagram.png'))->toBeTrue();
    expect(file_get_contents($this->outputDir.'/'.$docsPrefix.'/media/getting-started/diagram.png'))->toBe('fake-image-data');

    unlink($chapterDir.'/diagram.png');
});

it('copies media files from blog content dirs', function (): void {
    $blogPath = config('pergament.content_path').'/'.config('pergament.blog.path', 'blog');
    $postDir = $blogPath.'/2024-01-15-hello-world';
    file_put_contents($postDir.'/cover.jpg', 'fake-cover-data');

    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $blogPrefix = config('pergament.blog.url_prefix', 'blog');

    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/media/hello-world/cover.jpg'))->toBeTrue();
    expect(file_get_contents($this->outputDir.'/'.$blogPrefix.'/media/hello-world/cover.jpg'))->toBe('fake-cover-data');

    unlink($postDir.'/cover.jpg');
});

it('overrides prefix with --prefix option', function (): void {
    config()->set('pergament.prefix', 'landingpage-whatever');

    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
        '--prefix' => '/',
    ])->assertSuccessful();

    expect(config('pergament.prefix'))->toBe('landingpage-whatever');
    expect(file_exists($this->outputDir.'/index.html'))->toBeTrue();
});

it('removes existing output with --clean option', function (): void {
    mkdir($this->outputDir, 0755, true);
    file_put_contents($this->outputDir.'/old-file.txt', 'should be removed');

    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
        '--clean' => true,
    ])->assertSuccessful();

    expect(file_exists($this->outputDir.'/old-file.txt'))->toBeFalse();
    expect(file_exists($this->outputDir.'/index.html'))->toBeTrue();
});

it('skips disabled features gracefully', function (): void {
    config()->set('pergament.docs.enabled', false);
    config()->set('pergament.blog.enabled', false);
    config()->set('pergament.pages.enabled', false);
    config()->set('pergament.sitemap.enabled', false);
    config()->set('pergament.robots.enabled', false);
    config()->set('pergament.llms.enabled', false);

    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    expect(file_exists($this->outputDir.'/index.html'))->toBeTrue();

    expect(file_exists($this->outputDir.'/docs'))->toBeFalse();
    expect(file_exists($this->outputDir.'/blog'))->toBeFalse();
    expect(file_exists($this->outputDir.'/sitemap.xml'))->toBeFalse();
    expect(file_exists($this->outputDir.'/robots.txt'))->toBeFalse();
    expect(file_exists($this->outputDir.'/llms.txt'))->toBeFalse();
});

it('generates standalone page files with markdown sidecars', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    // "about" page is generated as a flat file (not "home", the homepage source).
    expect(file_exists($this->outputDir.'/about.html'))->toBeTrue();
    expect(file_get_contents($this->outputDir.'/about.html'))->toContain('About Us');

    expect(file_exists($this->outputDir.'/about.md'))->toBeTrue();
    expect(file_get_contents($this->outputDir.'/about.md'))->toContain('# About Us');
});

it('provides getSlugs method on PageService', function (): void {
    $service = app(PageService::class);
    $slugs = $service->getSlugs();

    expect($slugs)->toContain('home');
    expect($slugs)->toContain('about');
});

it('rewrites pagination query strings to static .html paths', function (): void {
    config()->set('pergament.blog.per_page', 1);

    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $blogPrefix = config('pergament.blog.url_prefix', 'blog');
    $content = file_get_contents($this->outputDir.'/'.$blogPrefix.'/page/1.html');

    expect($content)->not->toContain('?page=');
});

it('embeds sidecar css and js inline in static output', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    expect(file_exists($this->outputDir.'/about.html'))->toBeTrue();

    $html = file_get_contents($this->outputDir.'/about.html');

    expect($html)
        ->toContain('<style>.about-hero { color: rebeccapurple; }</style>')
        ->toContain("<script>console.log('about page');</script>");
});

it('reports broken content links during static generation', function (): void {
    config()->set('pergament.content_path', $this->tempDir);
    mkdir($this->tempDir.'/pages', 0755, true);
    file_put_contents($this->tempDir.'/pages/home.md', implode("\n", [
        '---',
        'title: Home',
        'excerpt: Home page',
        '---',
        '',
        '# Home',
        '',
        'Check the [missing page](./nonexistent.md).',
    ]));

    config()->set('pergament.docs.enabled', false);
    config()->set('pergament.blog.enabled', false);
    config()->set('pergament.sitemap.enabled', false);
    config()->set('pergament.robots.enabled', false);
    config()->set('pergament.llms.enabled', false);
    config()->set('pergament.pages.enabled', false);

    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful()
        ->expectsOutputToContain('Broken link');

    $content = file_get_contents($this->outputDir.'/index.html');
    expect($content)->toContain('missing page');
    expect($content)->not->toContain('nonexistent.md');
});
