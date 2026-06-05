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
        'tts' => false,
        'statistics' => [
            'reading_time' => false,
            'word_count' => false,
            'character_count' => false,
            'paragraph_count' => false,
            'last_modified' => false,
            'content_age' => false,
        ],
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
        'tts' => false,
        'default_authors' => [],
        'feed' => [
            'enabled' => true,
            'type' => 'atom',
            'title' => null,
            'description' => '',
            'limit' => 20,
        ],
        'statistics' => [
            'reading_time' => false,
            'word_count' => false,
            'character_count' => false,
            'paragraph_count' => false,
            'last_modified' => false,
            'content_age' => false,
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
        'tts' => false,
        'statistics' => [
            'reading_time' => false,
            'word_count' => false,
            'character_count' => false,
            'paragraph_count' => false,
            'last_modified' => false,
            'content_age' => false,
        ],
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
    | Path relative to the content directory (e.g. 'favicon.ico'), or an
    | absolute URL. Relative files are served from the content directory at
    | runtime and copied into the site root during static generation. Absolute
    | URLs are used verbatim and neither served nor copied.
    |
    */

    'favicon' => 'favicon.ico',

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
    | Text-to-Speech
    |--------------------------------------------------------------------------
    |
    | Global voice and rate settings for the browser's Speech Synthesis API.
    | To enable TTS per content type, set "tts" within the docs, blog, or
    | pages section above.
    |
    | voice: preferred voice name (browser-dependent). Set to null to use the
    |        browser default. Common voices across platforms:
    |
    |        macOS / iOS:
    |          "Samantha", "Alex", "Daniel", "Karen", "Moira",
    |          "Tessa", "Thomas", "Anna" (de), "Amelie" (fr)
    |
    |        Chrome (online):
    |          "Google UK English Female", "Google UK English Male",
    |          "Google US English", "Google Deutsch", "Google français"
    |
    |        Windows:
    |          "Microsoft David", "Microsoft Zira", "Microsoft Mark",
    |          "Microsoft Hedda" (de), "Microsoft Hortense" (fr)
    |
    |        Android:
    |          varies by device — typically uses the system TTS engine
    |
    | rate:  speech rate between 0.5 and 2.0 (1.0 = normal speed).
    |
    */

    'tts' => [
        'voice' => null,
        'rate' => 1.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Page Actions
    |--------------------------------------------------------------------------
    |
    | Optional toolbar for content pages. When enabled, visitors can copy the
    | raw markdown source, open the .md version, or start a chat with a
    | configured AI agent using the current page URL.
    |
    */

    'page_actions' => [
        'enabled' => false,
        'copy_markdown' => true,
        'open_markdown' => true,
        'ai_agents' => [
            'claude' => [
                'enabled' => true,
                'label' => 'Claude',
                'url' => 'https://claude.ai/new?q=I%E2%80%99d+like+to+discuss+the+content+from+{url}',
            ],
            'chatgpt' => [
                'enabled' => true,
                'label' => 'ChatGPT',
                'url' => 'https://chatgpt.com/?q=I%27d+like+to+discuss+the+content+from+{url}',
            ],
            'perplexity' => [
                'enabled' => true,
                'label' => 'Perplexity',
                'url' => 'https://www.perplexity.ai/?q=I%27d+like+to+discuss+the+content+from+{url}',
            ],
            'gemini' => [
                'enabled' => true,
                'label' => 'Gemini',
                'url' => 'https://gemini.google.com/app?q=I%27d+like+to+discuss+the+content+from+{url}',
            ],
        ],
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
        |--------------------------------------------------------------------------
        | Alerts
        |--------------------------------------------------------------------------
        |
        | GitHub-style alert components (NOTE, TIP, IMPORTANT, WARNING, CAUTION).
        | When enabled, blockquotes like "> [!NOTE]" are rendered as styled alerts.
        |
        */
        'alerts' => true,

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
    | Analytics
    |--------------------------------------------------------------------------
    |
    | Privacy-first, server-side page view tracking. Records the URL path,
    | a timestamp, and whether the request came from a bot — no IP addresses,
    | no user agents, no cookies, and no personal data of any kind.
    | No cookie banner required.
    |
    | Data is written as newline-delimited JSON (NDJSON) to one file per day:
    |   storage/pergament/analytics/YYYY-MM-DD.ndjson
    |
    | storage_path: override the directory where analytics files are stored.
    |               Defaults to storage_path('pergament/analytics').
    |
    | download.enabled: expose a URL to download the raw NDJSON log file.
    |                   Disabled by default — enable explicitly for production
    |                   access without shell.
    |
    | download.token:   secret token required to access the download URL.
    |                   Must be set by the developer before enabling the route.
    |                   Example: php artisan tinker --execute="echo bin2hex(random_bytes(32));"
    |                   Can be set with the PERGAMENT_ANALYTICS_TOKEN environment variable.
    |
    */

    'analytics' => [
        'enabled' => false,
        'storage_path' => null,

        'download' => [
            'enabled' => false,
            'token' => env('PERGAMENT_ANALYTICS_TOKEN'),
        ],
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
