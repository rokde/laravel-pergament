<?php

declare(strict_types=1);

namespace Pergament\Services;

use Illuminate\Support\Str;
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

        $html = Str::markdown($markdown, [
            'allow_unsafe_links' => false,
            'html_input' => 'allow',
        ]);

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
        $pathData = match ($type) {
            'note' => 'M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8Zm8-6.5a6.5 6.5 0 1 0 0 13 6.5 6.5 0 0 0 0-13ZM6.5 7.75A.75.75 0 0 1 7.25 7h1a.75.75 0 0 1 .75.75v2.75h.25a.75.75 0 0 1 0 1.5h-2a.75.75 0 0 1 0-1.5h.25v-2h-.25a.75.75 0 0 1-.75-.75ZM8 6a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z',
            'tip' => 'M8 1.5c-2.363 0-4 1.69-4 3.75 0 .984.424 1.625.984 2.304l.214.253c.223.264.47.556.673.848.284.411.537.896.621 1.49a.75.75 0 0 1-1.484.211c-.04-.282-.163-.547-.37-.847a8.456 8.456 0 0 0-.542-.68c-.084-.1-.173-.205-.268-.32C3.201 7.75 2.5 6.766 2.5 5.25 2.5 2.31 4.863 0 8 0s5.5 2.31 5.5 5.25c0 1.516-.701 2.5-1.328 3.259-.095.115-.184.22-.268.319-.207.245-.383.453-.541.681-.208.3-.33.565-.37.847a.751.751 0 0 1-1.485-.212c.084-.593.337-1.078.621-1.489.203-.292.45-.584.673-.848.075-.088.147-.173.213-.253.561-.679.985-1.32.985-2.304 0-2.06-1.637-3.75-4-3.75ZM5.75 12h4.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1 0-1.5ZM6 15.25a.75.75 0 0 1 .75-.75h2.5a.75.75 0 0 1 0 1.5h-2.5a.75.75 0 0 1-.75-.75Z',
            'important' => 'M0 1.75C0 .784.784 0 1.75 0h12.5C15.216 0 16 .784 16 1.75v9.5A1.75 1.75 0 0 1 14.25 13H8.06l-2.573 2.573A1.458 1.458 0 0 1 3 14.543V13H1.75A1.75 1.75 0 0 1 0 11.25Zm1.75-.25a.25.25 0 0 0-.25.25v9.5c0 .138.112.25.25.25h2a.75.75 0 0 1 .75.75v2.19l2.72-2.72a.749.749 0 0 1 .53-.22h6.5a.25.25 0 0 0 .25-.25v-9.5a.25.25 0 0 0-.25-.25Zm7 2.25v2.5a.75.75 0 0 1-1.5 0v-2.5a.75.75 0 0 1 1.5 0ZM9 9a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z',
            'warning' => 'M6.457 1.047c.659-1.234 2.427-1.234 3.086 0l6.082 11.378A1.75 1.75 0 0 1 14.082 15H1.918a1.75 1.75 0 0 1-1.543-2.575Zm1.763.707a.25.25 0 0 0-.44 0L1.698 13.132a.25.25 0 0 0 .22.368h12.164a.25.25 0 0 0 .22-.368Zm.53 3.996v2.5a.75.75 0 0 1-1.5 0v-2.5a.75.75 0 0 1 1.5 0ZM9 11a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z',
            'caution' => 'M4.47.22A.749.749 0 0 1 5 0h6c.199 0 .389.079.53.22l4.25 4.25c.141.14.22.331.22.53v6a.749.749 0 0 1-.22.53l-4.25 4.25A.749.749 0 0 1 11 16H5a.749.749 0 0 1-.53-.22L.22 11.53A.749.749 0 0 1 0 11V5c0-.199.079-.389.22-.53Zm.84 1.28L1.5 5.31v5.38l3.81 3.81h5.38l3.81-3.81V5.31L10.69 1.5ZM8 4a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z',
            default => '',
        };

        if ($pathData === '') {
            return '';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="'.e($pathData).'"/></svg>';
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
