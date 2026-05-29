<?php

declare(strict_types=1);

namespace Pergament\Support;

/**
 * Rewrites absolute, server-oriented URLs into relative paths so a generated
 * static site is fully self-contained and navigable from the filesystem
 * (file://) or any static host without a server.
 */
final class PortableLinkRewriter
{
    /**
     * @param  array<int, string>  $internalHosts  Hosts treated as part of this site (links to them become relative).
     * @param  string  $basePrefix  Global URL prefix without surrounding slashes (e.g. '' or 'app').
     * @param  string  $docsPrefix  Docs feature prefix without the base (e.g. 'docs').
     * @param  string  $blogPrefix  Blog feature prefix without the base (e.g. 'blog').
     */
    public function __construct(
        private array $internalHosts,
        private string $basePrefix,
        private string $docsPrefix,
        private string $blogPrefix,
    ) {}

    /**
     * Rewrite every internal href/src attribute in an HTML document.
     */
    public function rewriteHtml(string $html, string $currentRelPath, string $pageExtension = 'html'): string
    {
        return (string) preg_replace_callback(
            '/\b(href|src)=(["\'])(.*?)\2/i',
            function (array $m) use ($currentRelPath, $pageExtension): string {
                $rel = $this->relativize($m[3], $currentRelPath, $pageExtension);

                return $rel === null ? $m[0] : $m[1].'='.$m[2].$rel.$m[2];
            },
            $html,
        );
    }

    /**
     * Rewrite every internal link/image target in a Markdown document.
     */
    public function rewriteMarkdown(string $markdown, string $currentRelPath, string $pageExtension = 'md'): string
    {
        return (string) preg_replace_callback(
            '/(!?\[[^\]]*\]\()(\s*<?)([^)>\s]+)(>?\s*(?:"[^"]*")?\s*)(\))/',
            function (array $m) use ($currentRelPath, $pageExtension): string {
                $rel = $this->relativize($m[3], $currentRelPath, $pageExtension);

                return $m[1].$m[2].($rel ?? $m[3]).$m[4].$m[5];
            },
            $markdown,
        );
    }

    /**
     * Resolve a single URL to a relative path, or return it unchanged when external.
     */
    public function resolve(string $url, string $currentRelPath, string $pageExtension = 'html'): string
    {
        return $this->relativize($url, $currentRelPath, $pageExtension) ?? $url;
    }

    /**
     * Returns the relativized URL, or null when the URL must be left untouched (external, anchor, scheme).
     */
    private function relativize(string $url, string $currentRelPath, string $pageExtension): ?string
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '#')) {
            return null;
        }

        if (preg_match('#^(mailto:|tel:|javascript:|data:)#i', $url) === 1) {
            return null;
        }

        if (str_starts_with($url, '//')) {
            return null;
        }

        $fragment = '';
        if (($pos = strpos($url, '#')) !== false) {
            $fragment = substr($url, $pos);
            $url = substr($url, 0, $pos);
        }

        $query = '';
        if (($pos = strpos($url, '?')) !== false) {
            $query = substr($url, $pos + 1);
            $url = substr($url, 0, $pos);
        }

        if (preg_match('#^https?://#i', $url) === 1) {
            $host = parse_url($url, PHP_URL_HOST);

            if (! is_string($host) || ! in_array($host, $this->internalHosts, true)) {
                return null;
            }

            $path = (string) parse_url($url, PHP_URL_PATH);
        } elseif (str_starts_with($url, '/')) {
            $path = $url;
        } else {
            // Already a relative URL — leave it as authored.
            return null;
        }

        $targetFile = $this->pathToFile($path, $query, $pageExtension);

        if ($targetFile === null) {
            return null;
        }

        return $this->relativePath($currentRelPath, $targetFile).$fragment;
    }

    /**
     * Map a site-absolute path (+query) to the static output file that serves it.
     */
    private function pathToFile(string $path, string $query, string $pageExtension): ?string
    {
        $path = trim($path, '/');

        if ($this->basePrefix !== '') {
            if ($path === $this->basePrefix) {
                $path = '';
            } elseif (str_starts_with($path, $this->basePrefix.'/')) {
                $path = substr($path, mb_strlen($this->basePrefix) + 1);
            }
        }

        // Bundled assets live under assets/ in the export.
        if (str_starts_with($path, 'vendor/pergament/')) {
            return 'assets/'.substr($path, mb_strlen('vendor/pergament/'));
        }

        // Feed has a dedicated file name.
        if ($this->blogPrefix !== '' && $path === $this->blogPrefix.'/feed') {
            return $this->blogPrefix.'/feed.xml';
        }

        // Anything already carrying a file extension is a real file (media, images, xml…).
        if (preg_match('/\.[A-Za-z0-9]{1,8}$/', $path) === 1) {
            return $path;
        }

        // Pagination query becomes a path segment.
        if ($query !== '' && preg_match('/(?:^|&)page=(\d+)/', $query, $pm) === 1) {
            $path = ($path === '' ? '' : $path.'/').'page/'.$pm[1];
        }

        if ($path === '') {
            return 'index.html';
        }

        if ($path === $this->docsPrefix || $path === $this->blogPrefix) {
            return $path.'/index.html';
        }

        return $path.'.'.$pageExtension;
    }

    /**
     * Compute a relative path from the current file to a target file (both relative to output root).
     */
    private function relativePath(string $from, string $to): string
    {
        $fromParts = explode('/', $from);
        array_pop($fromParts);
        $toParts = explode('/', $to);

        while ($fromParts !== [] && $toParts !== [] && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        $up = str_repeat('../', count($fromParts));
        $rel = $up.implode('/', $toParts);

        return $rel === '' ? './' : $rel;
    }
}
