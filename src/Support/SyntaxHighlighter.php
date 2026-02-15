<?php

declare(strict_types=1);

namespace Pergament\Support;

use Tempest\Highlight\Highlighter;
use Throwable;

final class SyntaxHighlighter
{
    private Highlighter $highlighter;

    public function __construct()
    {
        $this->highlighter = new Highlighter;
    }

    /**
     * Highlight code with the given language.
     */
    public function highlight(string $code, string $language): string
    {
        if ($language === '' || $language === 'text' || $language === 'plaintext') {
            return e($code);
        }

        try {
            return $this->highlighter->parse($code, $language);
        } catch (Throwable) {
            return e($code);
        }
    }
}
