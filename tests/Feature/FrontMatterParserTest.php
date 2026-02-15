<?php

declare(strict_types=1);

use Pergament\Support\FrontMatterParser;

it('parses front matter and body', function (): void {
    $parser = resolve(FrontMatterParser::class);
    $result = $parser->parse("---\ntitle: Hello\nexcerpt: A test\n---\n\n# Body\n\nContent here.");

    expect($result['attributes']['title'])->toBe('Hello');
    expect($result['attributes']['excerpt'])->toBe('A test');
    expect($result['body'])->toContain('# Body');
    expect($result['body'])->toContain('Content here.');
});

it('returns empty attributes for no front matter', function (): void {
    $parser = resolve(FrontMatterParser::class);
    $result = $parser->parse("# Just Markdown\n\nNo front matter here.");

    expect($result['attributes'])->toBeEmpty();
    expect($result['body'])->toContain('# Just Markdown');
});

it('parses arrays in front matter', function (): void {
    $parser = resolve(FrontMatterParser::class);
    $result = $parser->parse("---\ntags: [a, b, c]\n---\n\nBody");

    expect($result['attributes']['tags'])->toBeArray();
    expect($result['attributes']['tags'])->toBe(['a', 'b', 'c']);
});

it('merges dot notation overrides', function (): void {
    $parser = resolve(FrontMatterParser::class);
    $defaults = ['title' => 'Default', 'description' => 'Default desc'];
    $overrides = ['title' => 'Custom'];

    $result = $parser->mergeWithDotNotation($defaults, $overrides);

    expect($result['title'])->toBe('Custom');
    expect($result['description'])->toBe('Default desc');
});
