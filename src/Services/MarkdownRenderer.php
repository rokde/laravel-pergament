<?php

declare(strict_types=1);

namespace Pergament\Services;

use Illuminate\Support\Str;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use Pergament\Data\DocHeading;
use Pergament\Support\SyntaxHighlighter;
use Pergament\Support\UrlGenerator;

final readonly class MarkdownRenderer
{
    public function __construct(
        private SyntaxHighlighter $highlighter,
    ) {}

    /**
     * Convert markdown to HTML.
     */
    public function toHtml(string $markdown): string
    {
        $markdown = str_replace(' -- ', ' — ', $markdown);

        $extensions = [];

        if (config('pergament.markdown.footnotes', false)) {
            $extensions[] = new FootnoteExtension;
        }

        $html = Str::markdown($markdown, [
            'allow_unsafe_links' => false,
            'html_input' => 'allow',
        ], $extensions);

        $html = $this->highlightCodeBlocks($html);
        $html = $this->addHeadingIds($html);
        $html = $this->processBlockDirectives($html);

        if (config('pergament.markdown.alerts.enabled', true)) {
            $html = $this->processAlerts($html);
        }

        return $html;
    }

    /**
     * Strip the first h1 from rendered HTML.
     */
    public function stripFirstH1(string $html): string
    {
        return (string) preg_replace('/<h1>.*?<\/h1>/s', '', $html, 1);
    }

    /**
     * Extract h2 and h3 headings for table of contents.
     *
     * @return array<int, DocHeading>
     */
    public function extractHeadings(string $html): array
    {
        $headings = [];

        preg_match_all('/<h([23])\s*id="([^"]*)"[^>]*>(.*?)<\/h[23]>/s', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $headings[] = new DocHeading(
                text: strip_tags($match[3]),
                slug: $match[2],
                level: (int) $match[1],
            );
        }

        return $headings;
    }

    /**
     * Resolve relative .md links in rendered HTML to their correct URLs.
     *
     * @return array{html: string, linkErrors: array<int, string>}
     */
    public function resolveContentLinks(string $html, string $sourceFilePath): array
    {
        $sourceDir = dirname($sourceFilePath);
        $linkErrors = [];

        $html = (string) preg_replace_callback(
            '/<a\s+([^>]*?)href="([^"]*\.md(?:#[^"]*)?)"([^>]*?)>([\s\S]*?)<\/a>/i',
            function (array $matches) use ($sourceDir, $sourceFilePath, &$linkErrors): string {
                $beforeHref = $matches[1];
                $href = $matches[2];
                $afterHref = $matches[3];
                $linkText = $matches[4];

                // Skip absolute URLs
                if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
                    return $matches[0];
                }

                // Separate anchor from path
                $anchor = '';
                if (str_contains($href, '#')) {
                    [$href, $anchor] = explode('#', $href, 2);
                    $anchor = '#'.$anchor;
                }

                $resolvedPath = $this->normalizePath($sourceDir.'/'.$href);

                if (! file_exists($resolvedPath)) {
                    $linkErrors[] = "Broken link to '{$matches[2]}' in ".basename($sourceFilePath);

                    return $linkText;
                }

                $url = $this->resolveFileToUrl($resolvedPath);

                if ($url === null) {
                    $linkErrors[] = "Cannot resolve URL for '{$matches[2]}' in ".basename($sourceFilePath);

                    return $linkText;
                }

                return '<a '.$beforeHref.'href="'.$url.$anchor.'"'.$afterHref.'>'.$linkText.'</a>';
            },
            $html,
        );

        return ['html' => $html, 'linkErrors' => $linkErrors];
    }

    private function resolveFileToUrl(string $filePath): ?string
    {
        $filePath = str_replace('\\', '/', $filePath);
        $contentPath = str_replace('\\', '/', mb_rtrim((string) config('pergament.content_path', 'content'), '/'));

        $docsPath = $contentPath.'/'.config('pergament.docs.path', 'docs');
        $blogPath = $contentPath.'/'.config('pergament.blog.path', 'blog');
        $pagesPath = $contentPath.'/'.config('pergament.pages.path', 'pages');

        if (str_starts_with($filePath, $docsPath.'/')) {
            $relative = mb_substr($filePath, mb_strlen($docsPath) + 1);
            $parts = explode('/', $relative);

            if (count($parts) === 2) {
                $chapterSlug = (string) preg_replace('/^\d+-/', '', $parts[0]);
                $pageSlug = (string) preg_replace('/^\d+-/', '', pathinfo($parts[1], PATHINFO_FILENAME));
                $docsPrefix = config('pergament.docs.url_prefix', 'docs');

                return UrlGenerator::path($docsPrefix, $chapterSlug, $pageSlug);
            }
        }

        if (str_starts_with($filePath, $blogPath.'/')) {
            $relative = mb_substr($filePath, mb_strlen($blogPath) + 1);
            $parts = explode('/', $relative);

            if (count($parts) === 2 && $parts[1] === 'post.md') {
                $slug = (string) preg_replace('/^\d{4}-\d{2}-\d{2}-/', '', $parts[0]);
                $blogPrefix = config('pergament.blog.url_prefix', 'blog');

                return UrlGenerator::path($blogPrefix, $slug);
            }
        }

        if (str_starts_with($filePath, $pagesPath.'/')) {
            $relative = mb_substr($filePath, mb_strlen($pagesPath) + 1);
            $slug = pathinfo($relative, PATHINFO_FILENAME);

            return UrlGenerator::path($slug);
        }

        return null;
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $parts = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '..') {
                array_pop($parts);
            } elseif ($segment !== '.' && $segment !== '') {
                $parts[] = $segment;
            }
        }

        $prefix = str_starts_with($path, '/') ? '/' : '';

        return $prefix.implode('/', $parts);
    }

    /**
     * Server-side syntax highlighting for code blocks.
     */
    private function highlightCodeBlocks(string $html): string
    {
        return (string) preg_replace_callback(
            '/<pre><code(?:\s+class="language-(\w+)")?>(.*?)<\/code><\/pre>/s',
            function (array $matches): string {
                $language = $matches[1] ?? '';
                $code = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                $highlighted = $this->highlighter->highlight($code, $language);
                $langAttr = $language !== '' ? ' data-language="'.e($language).'"' : '';

                return '<pre class="pergament-code-block"'.$langAttr.'><code>'.$highlighted.'</code></pre>';
            },
            $html,
        );
    }

    /**
     * Add slug-based IDs to h2 and h3 headings.
     */
    private function addHeadingIds(string $html): string
    {
        return (string) preg_replace_callback(
            '/<h([23])>(.*?)<\/h[23]>/s',
            function (array $matches): string {
                $level = $matches[1];
                $text = strip_tags($matches[2]);
                $slug = Str::slug($text);

                return '<h'.$level.' id="'.$slug.'">'.$matches[2].'</h'.$level.'>';
            },
            $html,
        );
    }

    /**
     * Transform GitHub-style alert blockquotes into styled alert components.
     *
     * Converts: > [!NOTE]\n> content
     * Into:     <div class="pergament-alert pergament-alert-note" role="alert">...</div>
     */
    private function processAlerts(string $html): string
    {
        return (string) preg_replace_callback(
            '/<blockquote>\s*<p>\[!(NOTE|TIP|IMPORTANT|WARNING|CAUTION)\]\n(.*?)<\/p>(.*?)<\/blockquote>/si',
            function (array $matches): string {
                $type = strtolower($matches[1]);
                $title = ucfirst($type);
                $firstContent = mb_trim($matches[2]);
                $remainingContent = mb_trim($matches[3]);

                $icon = $this->getAlertIcon($type);

                $content = '';

                if ($firstContent !== '') {
                    $content .= '<p>'.$firstContent.'</p>';
                }

                $content .= $remainingContent;

                return '<div class="pergament-alert pergament-alert-'.e($type).'" role="alert">'.
                    '<p class="pergament-alert-title">'.$icon.e($title).'</p>'.
                    '<div class="pergament-alert-content">'.$content.'</div>'.
                    '</div>';
            },
            $html,
        );
    }

    private function getAlertIcon(string $type): string
    {
        $inner = match ($type) {
            'note'      => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
            'tip'       => '<path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/>',
            'important' => '<circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>',
            'warning'   => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
            'caution'   => '<path d="M12 16h.01"/><path d="M12 8v4"/><path d="M15.312 2a2 2 0 0 1 1.414.586l4.688 4.688A2 2 0 0 1 22 8.688v6.624a2 2 0 0 1-.586 1.414l-4.688 4.688a2 2 0 0 1-1.414.586H8.688a2 2 0 0 1-1.414-.586l-4.688-4.688A2 2 0 0 1 2 15.312V8.688a2 2 0 0 1 .586-1.414l4.688-4.688A2 2 0 0 1 8.688 2z"/>',
            default     => '',
        };

        if ($inner === '') {
            return '';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$inner.'</svg>';
    }

    /**
     * Process block directives like :::hero, :::features, etc.
     * These map to CSS classes for styling.
     */
    private function processBlockDirectives(string $html): string
    {
        return (string) preg_replace_callback(
            '/<p>:::([\w-]+)<\/p>(.*?)<p>:::<\/p>/s',
            function (array $matches): string {
                $directive = $matches[1];
                $content = mb_trim($matches[2]);

                return '<div class="pergament-block pergament-block-'.e($directive).'">'.$content.'</div>';
            },
            $html,
        );
    }
}
