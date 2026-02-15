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
    $this->artisan('pergament:make-doc-page', [
        'chapter' => 'getting-started',
        'page' => 'installation',
        '--title' => 'Installation Guide',
        '--order' => '02',
    ])->assertSuccessful();

    $file = $this->tempDir.'/docs/02-getting-started/02-installation.md';
    expect(file_exists($file))->toBeTrue();
    expect(file_get_contents($file))->toContain('title: Installation Guide');
});

it('fails when doc page already exists', function (): void {
    $this->artisan('pergament:make-doc-page', [
        'chapter' => 'getting-started',
        'page' => 'intro',
        '--title' => 'Intro',
        '--order' => '01',
    ])->assertSuccessful();

    $this->artisan('pergament:make-doc-page', [
        'chapter' => 'getting-started',
        'page' => 'intro',
        '--title' => 'Intro',
        '--order' => '01',
    ])->assertFailed();
});

it('reuses existing chapter directory', function (): void {
    $docsPath = $this->tempDir.'/docs';
    mkdir($docsPath.'/0-getting-started', 0755, true);

    $this->artisan('pergament:make-doc-page', [
        'chapter' => 'getting-started',
        'page' => 'setup',
        '--title' => 'Setup',
        '--order' => '01',
    ])->assertSuccessful();

    $file = $this->tempDir.'/docs/0-getting-started/01-setup.md';
    expect(file_exists($file))->toBeTrue();
});

it('creates a new blog post', function (): void {
    $this->artisan('pergament:make-blog-post', [
        'slug' => 'my-first-post',
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
        'slug' => 'test-post',
        '--title' => 'Test',
        '--date' => '2024-01-01',
        '--excerpt' => '',
        '--category' => '',
        '--tags' => '',
        '--author' => '',
    ];

    $this->artisan('pergament:make-blog-post', $allOptions)->assertSuccessful();
    $this->artisan('pergament:make-blog-post', $allOptions)->assertFailed();
});
