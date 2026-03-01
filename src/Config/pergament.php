<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Content Path
    |--------------------------------------------------------------------------
    |
    | The base directory where all Pergament content files (docs, blog, pages) live.
    |
    */

    'content_path' => base_path('content'),

    /*
    |--------------------------------------------------------------------------
    | URL Prefix
    |--------------------------------------------------------------------------
    |
    | The base URL path where Pergament listens. All Pergament routes will be nested
    | under this prefix. Use "/" to take over the root, "docs" for /docs/*,
    | or any path like "landing-page/hello-world".
    |
    */

    'prefix' => '/',

    /*
    |--------------------------------------------------------------------------
    | Site Configuration
    |--------------------------------------------------------------------------
    |
    | Global site settings used across all pages. These can be overridden
    | in individual page/post front matter using dot notation.
    | e.g. "seo.title" in front matter overrides site.seo.title
    |
    */

    'site' => [
        'name' => env('APP_NAME', 'Pergament'),
        'url' => env('APP_URL', 'http://localhost'),
        'locale' => 'en',
        'seo' => [
            'title' => env('APP_NAME', 'Pergament'),
            'description' => '',
            'keywords' => '',
            'og_image' => '',
            'twitter_card' => 'summary_large_image',
            'robots' => 'index, follow',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Homepage
    |--------------------------------------------------------------------------
    |
    | Configure what content is displayed at the base URL.
    | Types: "page", "blog-index", "doc-page", "redirect"
    | For "page": source is the page slug (e.g. "home")
    | For "doc-page": source is "chapter/page" (e.g. "getting-started/introduction")
    | For "redirect": source is the target URL path
    |
    */

    'homepage' => [
        'type' => 'page',
        'source' => 'home',
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation
    |--------------------------------------------------------------------------
    */

    'docs' => [
        'enabled' => true,
        'path' => 'docs',
        'url_prefix' => 'docs',
        'title' => 'Documentation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Blog
    |--------------------------------------------------------------------------
    */

    'blog' => [
        'enabled' => true,
        'path' => 'blog',
        'url_prefix' => 'blog',
        'title' => 'Blog',
        'per_page' => 12,
        'default_authors' => [],
        'feed' => [
            'enabled' => true,
            'type' => 'atom',
            'title' => null,
            'description' => '',
            'limit' => 20,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    */

    'pages' => [
        'enabled' => true,
        'path' => 'pages',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap
    |--------------------------------------------------------------------------
    */

    'sitemap' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Robots.txt
    |--------------------------------------------------------------------------
    */

    'robots' => [
        'enabled' => true,
        'content' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | LLMs.txt
    |--------------------------------------------------------------------------
    */

    'llms' => [
        'enabled' => true,
        'content' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | PWA / Service Worker
    |--------------------------------------------------------------------------
    */

    'pwa' => [
        'enabled' => false,
        'name' => env('APP_NAME', 'Pergament'),
        'short_name' => env('APP_NAME', 'Pergament'),
        'description' => '',
        'theme_color' => '#ffffff',
        'background_color' => '#ffffff',
        'display' => 'standalone',
        'icons' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    |
    | Path relative to the content directory, or an absolute URL.
    |
    */

    'favicon' => null,

    /*
    |--------------------------------------------------------------------------
    | Colors
    |--------------------------------------------------------------------------
    |
    | Primary color drives all interactive UI elements: active states, focus
    | rings, badges, links, and highlights. Background sets the page surface
    | in light mode; dark mode always uses a near-black surface.
    | Both values accept any valid CSS color (hex, rgb, oklch, …).
    |
    */

    'colors' => [
        'primary' => '#3b82f6',
        'background' => '#ffffff',
    ],

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    'search' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown
    |--------------------------------------------------------------------------
    |
    | Configure Markdown rendering extensions and behavior.
    |
    */

    'markdown' => [

        /*
        |----------------------------------------------------------------------
        | Footnotes
        |----------------------------------------------------------------------
        |
        | Enable footnote support using the [^1] syntax. When enabled, footnote
        | references like [^1] in the text link to definitions at the bottom
        | of the document, similar to GitHub Flavored Markdown footnotes.
        |
        */

        'footnotes' => false,

    ],

    /*
    |--------------------------------------------------------------------------
    | Exports
    |--------------------------------------------------------------------------
    */

    'exports' => [

        /*
        |--------------------------------------------------------------------------
        | Markdown exports are made especially for llms
        |--------------------------------------------------------------------------
        */

        'markdown' => [
            'detection' => [
                /*
                |--------------------------------------------------------------------------
                | Detect for given user agents
                |--------------------------------------------------------------------------
                |
                | Requests from user agents containing any of these strings
                | will automatically receive a markdown response. Matching
                | is case-insensitive.
                */
                'user_agents' => [
                    'GPTBot',
                    'ClaudeBot',
                    'Claude-Web',
                    'Anthropic',
                    'ChatGPT-User',
                    'PerplexityBot',
                    'Bytespider',
                    'Google-Extended',
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Content Signals is for llms
            |--------------------------------------------------------------------------
            |
            | These signals are sent as a `Content-Signal` response header to
            | inform AI agents what they are allowed to do with your content.
            | Set to an empty array to disable the header entirely.
            |
            | See: https://contentstandards.org
            */
            'content_signals' => [
                'ai-train' => 'disallow',
                'ai-input' => 'allow',
                'search' => 'allow',
            ],
        ],
    ],
];
