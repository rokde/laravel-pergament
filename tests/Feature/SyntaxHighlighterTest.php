<?php

declare(strict_types=1);

use Pergament\Support\SyntaxHighlighter;

it('returns html-escaped code for empty language', function (): void {
    $highlighter = new SyntaxHighlighter;
    $code = 'echo "hello";';

    $result = $highlighter->highlight($code, '');

    expect($result)->toBe(e($code));
});

it('returns html-escaped code for language text', function (): void {
    $highlighter = new SyntaxHighlighter;
    $code = 'some plain text here';

    $result = $highlighter->highlight($code, 'text');

    expect($result)->toBe(e($code));
});

it('returns html-escaped code for language plaintext', function (): void {
    $highlighter = new SyntaxHighlighter;
    $code = 'some plain text here';

    $result = $highlighter->highlight($code, 'plaintext');

    expect($result)->toBe(e($code));
});

it('returns non-empty highlighted output for php language', function (): void {
    $highlighter = new SyntaxHighlighter;
    $code = 'echo "hello";';

    $result = $highlighter->highlight($code, 'php');

    expect($result)->toBeString()->not->toBeEmpty();
});

it('falls back to escaped code for unsupported language', function (): void {
    $highlighter = new SyntaxHighlighter;
    $code = 'some code here';

    $result = $highlighter->highlight($code, 'definitely-not-a-language');

    expect($result)->toBe(e($code));
});

it('properly escapes html entities in code with empty language', function (): void {
    $highlighter = new SyntaxHighlighter;
    $code = '<script>alert(1)</script>';

    $result = $highlighter->highlight($code, '');

    expect($result)->toContain('&lt;script&gt;');
    expect($result)->toContain('&lt;/script&gt;');
    expect($result)->not->toContain('<script>');
});
