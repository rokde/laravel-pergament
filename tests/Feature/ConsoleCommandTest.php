<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir().'/pergament-test-'.uniqid();
    mkdir($this->tempDir, 0755, true);
    config()->set('pergament.content_path', $this->tempDir);
});

afterEach(function (): void {
    $dir = $this->tempDir;
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
});

it('creates a new doc page', function (): void {
    $this->artisan('pergament:make:doc', [
        '--chapter' => 'getting-started',
        '--title' => 'Installation Guide',
        '--order' => '02',
    ])->assertSuccessful();

    // Chapter dir gets next available prefix (01, no existing chapters)
    $file = $this->tempDir.'/docs/01-getting-started/02-installation-guide.md';
    expect(file_exists($file))->toBeTrue();
    expect(file_get_contents($file))->toContain('title: Installation Guide');
});

it('slug is auto-derived from title', function (): void {
    $this->artisan('pergament:make:doc', [
        '--chapter' => 'reference',
        '--title' => 'API Authentication',
        '--order' => '01',
    ])->assertSuccessful();

    $file = $this->tempDir.'/docs/01-reference/01-api-authentication.md';
    expect(file_exists($file))->toBeTrue();
});

it('fails when doc page already exists', function (): void {
    $this->artisan('pergament:make:doc', [
        '--chapter' => 'getting-started',
        '--title' => 'Intro',
        '--order' => '01',
    ])->assertSuccessful();

    $this->artisan('pergament:make:doc', [
        '--chapter' => 'getting-started',
        '--title' => 'Intro',
        '--order' => '01',
    ])->assertFailed();
});

it('reuses existing chapter directory', function (): void {
    $docsPath = $this->tempDir.'/docs';
    mkdir($docsPath.'/0-getting-started', 0755, true);

    $this->artisan('pergament:make:doc', [
        '--chapter' => 'getting-started',
        '--title' => 'Setup',
        '--order' => '01',
    ])->assertSuccessful();

    $file = $this->tempDir.'/docs/0-getting-started/01-setup.md';
    expect(file_exists($file))->toBeTrue();
});

it('includes excerpt in doc page front matter', function (): void {
    $this->artisan('pergament:make:doc', [
        '--chapter' => 'getting-started',
        '--title' => 'Overview',
        '--excerpt' => 'A brief overview of the chapter.',
        '--order' => '01',
    ])->assertSuccessful();

    $file = $this->tempDir.'/docs/01-getting-started/01-overview.md';
    expect(file_get_contents($file))->toContain('excerpt: "A brief overview of the chapter."');
});

it('defaults to order 01 when chapter has no pages and no order is given', function (): void {
    $docsPath = $this->tempDir.'/docs';
    mkdir($docsPath.'/01-getting-started', 0755, true);

    $this->artisan('pergament:make:doc', [
        '--chapter' => 'getting-started',
        '--title' => 'First Page',
    ])->assertSuccessful();

    expect(file_exists($docsPath.'/01-getting-started/01-first-page.md'))->toBeTrue();
});

it('uses next available chapter prefix when creating a new chapter', function (): void {
    $docsPath = $this->tempDir.'/docs';
    mkdir($docsPath.'/01-getting-started', 0755, true);
    mkdir($docsPath.'/02-advanced', 0755, true);

    $this->artisan('pergament:make:doc', [
        '--chapter' => 'reference',
        '--title' => 'Overview',
        '--order' => '01',
    ])->assertSuccessful();

    expect(file_exists($docsPath.'/03-reference/01-overview.md'))->toBeTrue();
});

it('places page at end when position select returns last', function (): void {
    $docsPath = $this->tempDir.'/docs';
    mkdir($docsPath.'/01-getting-started', 0755, true);
    file_put_contents($docsPath.'/01-getting-started/01-intro.md', "---\ntitle: Intro\n---\n\n# Intro\n");

    $this->artisan('pergament:make:doc', [
        '--chapter' => 'getting-started',
        '--title' => 'Next Page',
    ])
        ->expectsQuestion('Position', 'last')
        ->assertSuccessful();

    expect(file_exists($docsPath.'/01-getting-started/02-next-page.md'))->toBeTrue();
});

it('places page at beginning when position select returns first', function (): void {
    $docsPath = $this->tempDir.'/docs';
    mkdir($docsPath.'/01-getting-started', 0755, true);
    file_put_contents($docsPath.'/01-getting-started/01-intro.md', "---\ntitle: Intro\n---\n\n# Intro\n");

    $this->artisan('pergament:make:doc', [
        '--chapter' => 'getting-started',
        '--title' => 'Preface',
    ])
        ->expectsQuestion('Position', 'first')
        ->assertSuccessful();

    expect(file_exists($docsPath.'/01-getting-started/00-preface.md'))->toBeTrue();
});

it('places page after a given page when position select returns after:N', function (): void {
    $docsPath = $this->tempDir.'/docs';
    mkdir($docsPath.'/01-getting-started', 0755, true);
    file_put_contents($docsPath.'/01-getting-started/01-intro.md', "---\ntitle: Intro\n---\n\n# Intro\n");
    file_put_contents($docsPath.'/01-getting-started/03-advanced.md', "---\ntitle: Advanced\n---\n\n# Advanced\n");

    $this->artisan('pergament:make:doc', [
        '--chapter' => 'getting-started',
        '--title' => 'Intermediate',
    ])
        ->expectsQuestion('Position', 'after:0') // after first page (prefix 01) → new prefix 02
        ->assertSuccessful();

    expect(file_exists($docsPath.'/01-getting-started/02-intermediate.md'))->toBeTrue();
});

it('creates a new blog post', function (): void {
    $this->artisan('pergament:make:post', [
        '--title' => 'My First Post',
        '--category' => 'General',
        '--tags' => 'laravel, php',
        '--author' => 'Jane Doe',
        '--date' => '2024-06-15',
        '--excerpt' => 'A short summary.',
    ])->assertSuccessful();

    $file = $this->tempDir.'/blog/2024-06-15-my-first-post/post.md';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)->toContain('title: "My First Post"');
    expect($content)->toContain('category: "General"');
    expect($content)->toContain('- "laravel"');
    expect($content)->toContain('- "php"');
    expect($content)->toContain('author: "Jane Doe"');
    expect($content)->toContain('excerpt: "A short summary."');
});

it('fails when blog post directory already exists', function (): void {
    $allOptions = [
        '--title' => 'Test',
        '--date' => '2024-01-01',
        '--excerpt' => '',
        '--category' => '',
        '--tags' => '',
        '--author' => '',
    ];

    $this->artisan('pergament:make:post', $allOptions)->assertSuccessful();
    $this->artisan('pergament:make:post', $allOptions)->assertFailed();
});
