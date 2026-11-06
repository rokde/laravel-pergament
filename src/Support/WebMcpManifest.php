<?php

declare(strict_types=1);

namespace Pergament\Support;

/**
 * Builds the /.well-known/webmcp discovery manifest.
 *
 * Not part of the WebMCP spec, but some agents and browser extensions
 * look for it to discover the tools a site offers.
 */
final class WebMcpManifest
{
    public static function enabled(): bool
    {
        return (bool) config('pergament.search.enabled', true)
            && (bool) config('pergament.search.webmcp', true);
    }

    public static function originTrialToken(): ?string
    {
        if (! self::enabled()) {
            return null;
        }

        $token = trim((string) config('pergament.search.webmcp_origin_trial', ''));

        return $token === '' ? null : $token;
    }

    /**
     * @return array<string, mixed>
     */
    public static function build(string $searchUrl): array
    {
        return [
            'name' => (string) config('pergament.site.name', ''),
            'description' => (string) config('pergament.site.seo.description', ''),
            'url' => UrlGenerator::url(''),
            'tools' => [
                [
                    'name' => 'search',
                    'description' => self::searchDescription(),
                    'method' => 'GET',
                    'url' => $searchUrl,
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => 'Text to search for'],
                        ],
                        'required' => ['query'],
                    ],
                    'annotations' => ['readOnlyHint' => true],
                ],
            ],
        ];
    }

    public static function toJson(string $searchUrl): string
    {
        return (string) json_encode(
            self::build($searchUrl),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    public static function searchDescription(): string
    {
        return 'Search this site\'s documentation, blog posts and pages. Returns matching titles, excerpts and URLs.';
    }
}
