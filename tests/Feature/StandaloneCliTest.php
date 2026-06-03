<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

beforeEach(function (): void {
    $this->outputDir = sys_get_temp_dir().'/pergament-standalone-'.uniqid();
    $this->contentDir = sys_get_temp_dir().'/pergament-standalone-content-'.uniqid();
});

afterEach(function (): void {
    foreach ([$this->outputDir, $this->contentDir] as $directory) {
        if (! is_dir($directory)) {
            continue;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }

        rmdir($directory);
    }
});

it('generates a static site through the standalone binary', function (): void {
    $process = new Process([
        PHP_BINARY,
        __DIR__.'/../../bin/pergament',
        'generate-static',
        $this->outputDir,
        '--content-path='.__DIR__.'/../fixtures/content',
        '--base-url=https://example.github.io/pergament',
        '--clean',
    ], __DIR__.'/../..');

    $process->setTimeout(60);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput().$process->getOutput());
    expect(file_exists($this->outputDir.'/index.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/docs/getting-started/introduction.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/blog/hello-world.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/search.json'))->toBeTrue();
    expect(file_exists($this->outputDir.'/sitemap.xml'))->toBeTrue();
});

it('supports the existing artisan command name as an alias', function (): void {
    $process = new Process([
        PHP_BINARY,
        __DIR__.'/../../bin/pergament',
        'pergament:generate-static',
        $this->outputDir,
        '--content-path='.__DIR__.'/../fixtures/content',
        '--clean',
    ], __DIR__.'/../..');

    $process->setTimeout(60);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput().$process->getOutput());
    expect(file_exists($this->outputDir.'/index.html'))->toBeTrue();
});

it('reports broken content links through the standalone binary', function (): void {
    mkdir($this->contentDir.'/pages', 0755, true);
    file_put_contents($this->contentDir.'/pages/home.md', implode("\n", [
        '---',
        'title: Home',
        'excerpt: Home page',
        '---',
        '',
        '# Home',
        '',
        'Check the [missing page](./nonexistent.md).',
    ]));

    $process = new Process([
        PHP_BINARY,
        __DIR__.'/../../bin/pergament',
        'generate-static',
        $this->outputDir,
        '--content-path='.$this->contentDir,
        '--clean',
    ], __DIR__.'/../..');

    $process->setTimeout(60);
    $process->run();

    $output = $process->getErrorOutput().$process->getOutput();

    expect($process->isSuccessful())->toBeTrue($output);
    expect($output)->toContain('Broken link');
    expect($output)->not->toContain('Target class [log] does not exist');
});

it('returns a non-zero status for unknown standalone commands', function (): void {
    $process = new Process([
        PHP_BINARY,
        __DIR__.'/../../bin/pergament',
        'unknown-command',
    ], __DIR__.'/../..');

    $process->setTimeout(60);
    $process->run();

    expect($process->isSuccessful())->toBeFalse();
    expect($process->getErrorOutput().$process->getOutput())->toContain('Unknown command');
});
