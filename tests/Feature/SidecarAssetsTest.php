<?php

declare(strict_types=1);

use Pergament\Support\SidecarAssets;

it('returns css and js contents for a markdown file', function () {
    $dir = sys_get_temp_dir().'/pergament-sidecar-'.uniqid();
    mkdir($dir);
    file_put_contents($dir.'/home.md', '# Home');
    file_put_contents($dir.'/home.css', '.hero { color: red; }');
    file_put_contents($dir.'/home.js', "console.log('hi');");

    $result = SidecarAssets::forMarkdownFile($dir.'/home.md');

    expect($result['styles'])->toBe('.hero { color: red; }')
        ->and($result['scripts'])->toBe("console.log('hi');");
});

it('returns null for missing sidecars', function () {
    $dir = sys_get_temp_dir().'/pergament-sidecar-'.uniqid();
    mkdir($dir);
    file_put_contents($dir.'/home.md', '# Home');

    $result = SidecarAssets::forMarkdownFile($dir.'/home.md');

    expect($result['styles'])->toBeNull()
        ->and($result['scripts'])->toBeNull();
});

it('returns nulls for a null path', function () {
    $result = SidecarAssets::forMarkdownFile(null);

    expect($result['styles'])->toBeNull()
        ->and($result['scripts'])->toBeNull();
});

it('trims sidecar contents', function () {
    $dir = sys_get_temp_dir().'/pergament-sidecar-'.uniqid();
    mkdir($dir);
    file_put_contents($dir.'/home.md', '# Home');
    file_put_contents($dir.'/home.css', "\n.hero {}\n\n");

    $result = SidecarAssets::forMarkdownFile($dir.'/home.md');

    expect($result['styles'])->toBe('.hero {}');
});
