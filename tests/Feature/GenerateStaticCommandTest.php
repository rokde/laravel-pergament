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

it('generates doc pages in correct directory structure', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $docsPrefix = config('pergament.docs.url_prefix', 'docs');

    // Doc index redirect
    expect(file_exists($this->outputDir.'/'.$docsPrefix.'/index.html'))->toBeTrue();
    expect(file_get_contents($this->outputDir.'/'.$docsPrefix.'/index.html'))->toContain('meta http-equiv="refresh"');

    // Doc pages
    expect(file_exists($this->outputDir.'/'.$docsPrefix.'/getting-started/introduction/index.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/'.$docsPrefix.'/getting-started/configuration/index.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/'.$docsPrefix.'/advanced/customization/index.html'))->toBeTrue();

    $content = file_get_contents($this->outputDir.'/'.$docsPrefix.'/getting-started/introduction/index.html');
    expect($content)->toContain('Introduction');
});

it('generates blog index with pagination', function (): void {
    config()->set('pergament.blog.per_page', 1);

    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $blogPrefix = config('pergament.blog.url_prefix', 'blog');

    // Page 1
    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/index.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/page/1/index.html'))->toBeTrue();

    // Page 2
    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/page/2/index.html'))->toBeTrue();
});

it('generates individual blog post files', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $blogPrefix = config('pergament.blog.url_prefix', 'blog');

    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/hello-world/index.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/getting-started-with-laravel/index.html'))->toBeTrue();

    $content = file_get_contents($this->outputDir.'/'.$blogPrefix.'/hello-world/index.html');
    expect($content)->toContain('Hello World');
});

it('generates category pages', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $blogPrefix = config('pergament.blog.url_prefix', 'blog');

    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/category/general/index.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/category/tutorials/index.html'))->toBeTrue();
});

it('generates tag pages', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $blogPrefix = config('pergament.blog.url_prefix', 'blog');

    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/tag/intro/index.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/tag/laravel/index.html'))->toBeTrue();
});

it('generates author pages', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $blogPrefix = config('pergament.blog.url_prefix', 'blog');

    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/author/jane-doe/index.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/author/john-smith/index.html'))->toBeTrue();
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

it('generates feed as blog/feed/index.xml', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $blogPrefix = config('pergament.blog.url_prefix', 'blog');

    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/feed/index.xml'))->toBeTrue();
    expect(file_get_contents($this->outputDir.'/'.$blogPrefix.'/feed/index.xml'))->toContain('<feed');
});

it('copies media files from doc content dirs', function (): void {
    // Create a media file in a doc chapter
    $docsPath = config('pergament.content_path').'/'.config('pergament.docs.path', 'docs');
    $chapterDir = $docsPath.'/0-getting-started';
    file_put_contents($chapterDir.'/diagram.png', 'fake-image-data');

    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $docsPrefix = config('pergament.docs.url_prefix', 'docs');

    expect(file_exists($this->outputDir.'/'.$docsPrefix.'/media/getting-started/diagram.png'))->toBeTrue();
    expect(file_get_contents($this->outputDir.'/'.$docsPrefix.'/media/getting-started/diagram.png'))->toBe('fake-image-data');

    // Clean up the test media file
    unlink($chapterDir.'/diagram.png');
});

it('copies media files from blog content dirs', function (): void {
    // Create a media file in a blog post dir
    $blogPath = config('pergament.content_path').'/'.config('pergament.blog.path', 'blog');
    $postDir = $blogPath.'/2024-01-15-hello-world';
    file_put_contents($postDir.'/cover.jpg', 'fake-cover-data');

    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $blogPrefix = config('pergament.blog.url_prefix', 'blog');

    expect(file_exists($this->outputDir.'/'.$blogPrefix.'/media/hello-world/cover.jpg'))->toBeTrue();
    expect(file_get_contents($this->outputDir.'/'.$blogPrefix.'/media/hello-world/cover.jpg'))->toBe('fake-cover-data');

    // Clean up the test media file
    unlink($postDir.'/cover.jpg');
});

it('overrides prefix with --prefix option', function (): void {
    config()->set('pergament.prefix', 'landingpage-whatever');

    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
        '--prefix' => '/',
    ])->assertSuccessful();

    // After the command, the original prefix should be restored
    expect(config('pergament.prefix'))->toBe('landingpage-whatever');

    // Homepage should still be generated at root
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

    // Homepage should still exist
    expect(file_exists($this->outputDir.'/index.html'))->toBeTrue();

    // Disabled features should not exist
    expect(file_exists($this->outputDir.'/docs'))->toBeFalse();
    expect(file_exists($this->outputDir.'/blog'))->toBeFalse();
    expect(file_exists($this->outputDir.'/sitemap.xml'))->toBeFalse();
    expect(file_exists($this->outputDir.'/robots.txt'))->toBeFalse();
    expect(file_exists($this->outputDir.'/llms.txt'))->toBeFalse();
});

it('generates standalone page files', function (): void {
    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    // "about" page should be generated (not "home" since it's the homepage source)
    expect(file_exists($this->outputDir.'/about/index.html'))->toBeTrue();
    expect(file_get_contents($this->outputDir.'/about/index.html'))->toContain('About Us');
});

it('provides getSlugs method on PageService', function (): void {
    $service = app(PageService::class);
    $slugs = $service->getSlugs();

    expect($slugs)->toContain('home');
    expect($slugs)->toContain('about');
});

it('rewrites pagination query strings to static paths', function (): void {
    config()->set('pergament.blog.per_page', 1);

    $this->artisan('pergament:generate-static', [
        'output-dir' => $this->outputDir,
    ])->assertSuccessful();

    $blogPrefix = config('pergament.blog.url_prefix', 'blog');
    $content = file_get_contents($this->outputDir.'/'.$blogPrefix.'/page/1/index.html');

    // Should not contain ?page= query strings
    expect($content)->not->toContain('?page=');
});

it('reports broken content links during static generation', function (): void {
    // Create a temp content dir with a page containing a broken link
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

    // The generated page should not contain an <a> tag for the broken link
    $content = file_get_contents($this->outputDir.'/index.html');
    expect($content)->toContain('missing page');
    expect($content)->not->toContain('nonexistent.md');
});
