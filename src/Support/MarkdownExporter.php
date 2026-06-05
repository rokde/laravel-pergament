<?php

declare(strict_types=1);

namespace Pergament\Support;

use League\HTMLToMarkdown\HtmlConverter;

/**
 * Converts rendered page HTML into a token-safe Markdown export.
 *
 * Shared by the static site generator (sidecar .md files) and the runtime
 * Markdown responses (.md URLs, LLM/agent requests) so both produce identical
 * output: style/script blocks are dropped entirely and all remaining HTML tags
 * are unwrapped to their inner prose.
 */
final class MarkdownExporter
{
    private HtmlConverter $converter;

    public function __construct(?HtmlConverter $converter = null)
    {
        $this->converter = $converter ?? new HtmlConverter(['hard_break' => true, 'strip_tags' => true]);
    }

    public function fromHtml(string $html, string $title = ''): string
    {
        $fragment = $this->stripNonContentHtml($html);
        $body = mb_trim($this->converter->convert($fragment));
        $heading = $title !== '' ? '# '.$title."\n\n" : '';

        return $heading.$body."\n";
    }

    /**
     * Drop tags whose content is not part of the readable document. The HtmlConverter
     * (strip_tags) removes remaining structural tags but keeps their inner text, so
     * style/script must go first along with their contents.
     */
    private function stripNonContentHtml(string $html): string
    {
        return (string) preg_replace('#<(style|script)\b[^>]*>.*?</\1>#is', '', $html);
    }
}
