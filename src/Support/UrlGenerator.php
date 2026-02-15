<?php

declare(strict_types=1);

namespace Pergament\Support;

final class UrlGenerator
{
    /**
     * Get the base prefix for all Pergament routes (normalized, no trailing slash).
     */
    public static function basePrefix(): string
    {
        $prefix = mb_trim(config('pergament.prefix', '/'), '/');

        return $prefix === '' ? '' : $prefix;
    }

    /**
     * Build a full Pergament path from segments, prepending the base prefix.
     */
    public static function path(string ...$segments): string
    {
        $base = self::basePrefix();
        $path = implode('/', array_filter($segments, fn (string $s): bool => $s !== ''));

        if ($base === '') {
            return '/'.$path;
        }

        return '/'.$base.($path !== '' ? '/'.$path : '');
    }

    /**
     * Build a full absolute URL from segments (site URL + base prefix + segments).
     */
    public static function url(string ...$segments): string
    {
        $siteUrl = mb_rtrim((string) config('pergament.site.url', ''), '/');

        return $siteUrl.self::path(...$segments);
    }

    /**
     * Build the full docs prefix path.
     */
    public static function docsPrefix(): string
    {
        return self::combinePrefixes(config('pergament.docs.url_prefix', 'docs'));
    }

    /**
     * Build the full blog prefix path.
     */
    public static function blogPrefix(): string
    {
        return self::combinePrefixes(config('pergament.blog.url_prefix', 'blog'));
    }

    /**
     * Combine the base prefix with a feature prefix.
     */
    private static function combinePrefixes(string $featurePrefix): string
    {
        $base = self::basePrefix();

        if ($base === '') {
            return $featurePrefix;
        }

        return $base.'/'.$featurePrefix;
    }
}
