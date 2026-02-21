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

it('unquotes single-quoted string values', function (): void {
    $parser = resolve(FrontMatterParser::class);
    $result = $parser->parse("---\nvalue: 'hello world'\n---\n\nbody");

    expect($result['attributes']['value'])->toBe('hello world');
});

it('unquotes double-quoted string values', function (): void {
    $parser = resolve(FrontMatterParser::class);
    $result = $parser->parse("---\nvalue: \"hello world\"\n---\n\nbody");

    expect($result['attributes']['value'])->toBe('hello world');
});

it('casts true to boolean true', function (): void {
    $parser = resolve(FrontMatterParser::class);
    $result = $parser->parse("---\npublished: true\n---\n\nbody");

    expect($result['attributes']['published'])->toBeTrue();
});

it('casts false to boolean false', function (): void {
    $parser = resolve(FrontMatterParser::class);
    $result = $parser->parse("---\ndraft: false\n---\n\nbody");

    expect($result['attributes']['draft'])->toBeFalse();
});

it('casts null to PHP null', function (): void {
    $parser = resolve(FrontMatterParser::class);
    $result = $parser->parse("---\nauthor: null\n---\n\nbody");

    expect($result['attributes']['author'])->toBeNull();
});

it('casts float values correctly', function (): void {
    $parser = resolve(FrontMatterParser::class);
    $result = $parser->parse("---\nrating: 1.5\n---\n\nbody");

    expect($result['attributes']['rating'])->toBe(1.5);
});

it('skips YAML comment lines starting with hash', function (): void {
    $parser = resolve(FrontMatterParser::class);
    $result = $parser->parse("---\ntitle: Real Title\n# this is a comment\nauthor: Jane\n---\n\nbody");

    expect($result['attributes']['title'])->toBe('Real Title');
    expect($result['attributes']['author'])->toBe('Jane');
    expect($result['attributes'])->not->toHaveKey('#');
});

it('parses dash-notation multi-line list', function (): void {
    $parser = resolve(FrontMatterParser::class);
    $result = $parser->parse("---\ntags:\n  - laravel\n  - php\n---\n\nbody");

    expect($result['attributes']['tags'])->toBe(['laravel', 'php']);
});

it('merges dot notation key into nested array', function (): void {
    $parser = resolve(FrontMatterParser::class);
    $defaults = ['seo' => ['title' => 'Default SEO', 'description' => 'Default SEO desc']];
    $overrides = ['seo.title' => 'Custom SEO'];

    $result = $parser->mergeWithDotNotation($defaults, $overrides);

    expect($result['seo']['title'])->toBe('Custom SEO');
    expect($result['seo']['description'])->toBe('Default SEO desc');
});

it('returns empty attributes and empty body for front matter with empty YAML', function (): void {
    $parser = resolve(FrontMatterParser::class);
    $result = $parser->parse("---\n---");

    expect($result['attributes'])->toBeEmpty();
    expect($result['body'])->toBe('');
});
