# Laravel Pergament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rokde/laravel-pergament.svg?style=flat-square)](https://packagist.org/packages/rokde/laravel-pergament)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/rokde/laravel-pergament/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/rokde/laravel-pergament/actions/workflows/tests.yml?query=branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/rokde/laravel-pergament.svg?style=flat-square)](https://packagist.org/packages/rokde/laravel-pergament)

A file-based CMS package for Laravel. Renders documentation, blog posts, and standalone pages from Markdown files with YAML front matter. Blade templates, Tailwind CSS, dark mode, server-side syntax highlighting — no database required.

## Installation

Add the package via Composer:

```bash
composer require rokde/laravel-pergament
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=pergament-config
```

Publish the views (optional, for customization):

```bash
php artisan vendor:publish --tag=pergament-views
```

Publish only the header component (navigation bar, search, font controls, dark mode toggle):

```bash
php artisan vendor:publish --tag=pergament-header
```

Publish only the footer component (copyright bar):

```bash
php artisan vendor:publish --tag=pergament-footer
```

## Configuration

The main config file is `config/pergament.php`. Key options:

### Base URL Prefix

Control where Pergament listens. All routes are nested under this prefix:

```php
'prefix' => '/',                    // Pergament owns the root
'prefix' => 'docs',                 // Pergament lives at /docs/*
'prefix' => 'landing-page/hello',   // Pergament lives at /landing-page/hello/*
```

### Content Path

Where your Markdown content lives on disk:

```php
'content_path' => base_path('content'),
```

### Homepage

Configure what shows at the base URL:

```php
'homepage' => [
    'type' => 'page',        // "page", "blog-index", "doc-page", or "redirect"
    'source' => 'home',      // page slug, "chapter/page", or redirect target
],
```

### Documentation

```php
'docs' => [
    'enabled' => true,
    'path' => 'docs',            // subfolder under content_path
    'url_prefix' => 'docs',      // URL segment: /prefix/docs/chapter/page
    'title' => 'Documentation',
],
```

### Blog

```php
'blog' => [
    'enabled' => true,
    'path' => 'blog',
    'url_prefix' => 'blog',
    'title' => 'Blog',
    'per_page' => 12,
    'default_authors' => [],
    'feed' => [
        'enabled' => true,
        'type' => 'atom',        // "atom" or "rss"
        'title' => null,
        'description' => '',
        'limit' => 20,
    ],
],
```

### Colors & Theming

Configure your brand color and page background. Both values propagate as CSS custom properties (`--p-primary`, `--p-bg`) that drive the entire UI — navigation highlights, badges, links, scrollbars, focus rings, text selection, and more:

```php
'colors' => [
    'primary'    => '#3b82f6',   // any CSS color: hex, oklch, named…
    'background' => '#ffffff',
],
```

Dark mode is handled automatically: the background switches to a dark slate (`#111827`) and tints derived from `--p-primary` re-resolve against it without any extra configuration.

### Site & SEO

```php
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
```

### Text-to-Speech

Add a play/pause button that reads page content aloud using the browser's [Speech Synthesis API](https://developer.mozilla.org/en-US/docs/Web/API/SpeechSynthesis). Each content type can be enabled independently:

```php
'tts' => [
    'blog'  => true,            // show on blog posts
    'docs'  => true,            // show on documentation pages
    'pages' => false,           // show on standalone pages
    'voice' => 'Samantha',      // preferred voice name (null = browser default)
    'rate'  => 1.0,             // speech rate: 0.5 (slow) to 2.0 (fast)
],
```

The `voice` value is browser- and OS-dependent. Common options:

| Platform | Voices |
|----------|--------|
| macOS / iOS | `Samantha`, `Alex`, `Daniel`, `Karen`, `Moira`, `Thomas` |
| Chrome | `Google UK English Female`, `Google UK English Male`, `Google US English` |
| Windows | `Microsoft David`, `Microsoft Zira`, `Microsoft Mark` |

If the chosen voice is unavailable, the browser's default voice is used as fallback.

## Content Structure

```
content/
├── docs/
│   ├── 0-getting-started/
│   │   ├── 01-introduction.md
│   │   └── 02-installation.md
│   └── 1-configuration/
│       └── 01-basic-setup.md
├── blog/
│   ├── 2024-01-15-hello-world/
│   │   ├── post.md
│   │   └── hero.png
│   └── 2024-03-20-new-feature/
│       └── post.md
└── pages/
    ├── home.md
    ├── about.md
    └── pricing.md
```

### Documentation

Documentation lives in numbered chapter directories. Each chapter contains numbered Markdown files:

- Directory format: `{order}-{chapter-slug}/` (e.g. `0-getting-started/`)
- File format: `{order}-{page-slug}.md` (e.g. `01-introduction.md`)
- The numeric prefixes control sort order and are stripped from URLs

### Blog Posts

Blog posts live in date-prefixed directories:

- Directory format: `{YYYY-MM-DD}-{slug}/` (e.g. `2024-01-15-hello-world/`)
- Each directory contains a `post.md` file and any associated media files
- The date is extracted from the directory name

### Standalone Pages

Simple Markdown files in the `pages/` directory. The filename (without `.md`) becomes the URL slug.

## Front Matter

All content files use YAML front matter delimited by `---`:

```markdown
---
title: My Page Title
excerpt: A brief description shown on index pages
---

# My Page Title

Content goes here.
```

### Documentation Front Matter

```yaml
---
title: Introduction
excerpt: Getting started with Pergament
---
```

### Blog Post Front Matter

```yaml
---
title: "Hello World"
excerpt: "Our very first blog post"
category: "Announcements"
tags:
  - "laravel"
  - "pergament"
author: "Jane Doe"
---
```

You can also define multiple authors with details:

```yaml
authors:
  - name: "Jane Doe"
    email: "jane@example.com"
    url: "https://janedoe.com"
    avatar: "https://example.com/avatar.jpg"
  - name: "John Smith"
```

### Page Front Matter

```yaml
---
title: About Us
excerpt: Learn more about our company
layout: landing
---
```

Set `layout: landing` to use the full-width landing page layout instead of the default centered content layout.

### SEO Overrides

Any page can override global SEO settings using dot notation in its front matter:

```yaml
---
title: My Page
seo.title: "Custom SEO Title - My Site"
seo.description: "A custom meta description for this specific page"
seo.og_image: "https://example.com/special-og.png"
seo.robots: "noindex, nofollow"
---
```

These override the corresponding values from `config('pergament.site.seo.*')`.

## GitHub-Style Alerts

Pergament supports GitHub-style alert blocks for highlighting important information. Alerts are enabled by default.

### Syntax

```markdown
> [!NOTE]
> Useful information that users should know, even when skimming.

> [!TIP]
> Helpful advice for doing things better or more easily.

> [!IMPORTANT]
> Key information users need to know to achieve their goal.

> [!WARNING]
> Urgent info that needs immediate user attention to avoid problems.

> [!CAUTION]
> Advises about risks or negative outcomes of certain actions.
```

### Alert Types

| Type | Purpose | Color |
|------|---------|-------|
| `NOTE` | Supplementary information | Blue |
| `TIP` | Helpful advice | Green |
| `IMPORTANT` | Key information | Purple |
| `WARNING` | Urgent notices | Amber |
| `CAUTION` | Risk warnings | Red |

Each alert renders as a styled `<div>` with an icon, title, and content. Dark mode variants are included automatically.

### Configuration

```php
'markdown' => [
    'alerts' => true,   // set to false to disable alert rendering
],
```

When disabled, alert syntax is rendered as a plain blockquote.

## Downloads

Pergament automatically handles file download links in blog posts and documentation pages.

### Relative download links

Any relative link in a blog post or documentation page that points to a file (not an external URL, anchor, or another Markdown page) is automatically rewritten to the correct media URL:

```markdown
[Download the guide](guide.pdf)
[Get the source archive](src.zip)
```

In a blog post, these become `/blog/media/{slug}/guide.pdf` and `/blog/media/{slug}/src.zip`. In a documentation page they resolve to the equivalent docs media path. Place the files in the same directory as your `post.md` or doc Markdown file.

External links (`http://`, `https://`), anchors (`#`), `mailto:` links, and links to other Markdown pages (`.md`) are left unchanged.

### `:::download` block directive

Wrap one or more links in a `:::download` block to add the HTML `download` attribute, which instructs the browser to download the file instead of navigating to it:

```markdown
:::download

[Download PDF](report.pdf)
[Download CSV](data.csv)

:::
```

Links inside a `:::download` block receive the `download` attribute — the browser saves the file to disk rather than opening it. The relative-path URL rewriting described above still applies. External links and anchors inside the block are not given the `download` attribute.

## Footnotes

Pergament supports inline footnotes via the CommonMark `FootnoteExtension`. Footnotes are disabled by default.

### Syntax

```markdown
Here is a sentence with a footnote.[^1]

You can have multiple footnotes[^2] in a single document[^3].

[^1]: This is the first footnote.
[^2]: This is the second footnote.
[^3]: Footnotes can contain **Markdown** formatting.
```

Footnote references render as superscript links (`<sup>`) that anchor to the footnote list at the bottom of the page. Each footnote includes a backlink to return to the reference in the text.

### Configuration

```php
'markdown' => [
    'footnotes' => false,   // set to true to enable footnote support
],
```

## Block-Based Landing Pages

For landing pages and homepages, you can use block directives in Markdown to create structured sections. Block directives wrap content in `<div>` elements with CSS classes for styling.

### Syntax

```markdown
:::hero

# Welcome to Our Product

The best solution for your needs.

[Get Started](/docs/getting-started/introduction)

:::

:::features

## Why Choose Us

- **Fast** — Built for speed
- **Reliable** — 99.9% uptime
- **Simple** — Easy to use

:::

:::cta

## Ready to Get Started?

Sign up today and see the difference.

[Sign Up Free](/register)

:::
```

Each `:::{name}` block becomes a `<div class="pergament-block pergament-block-{name}">` in the rendered HTML. The closing `:::` ends the block.

### Built-in Block Types

The default views include basic styles for these block types:

| Directive | CSS Class | Purpose |
|-----------|-----------|---------|
| `:::hero` | `pergament-block-hero` | Hero sections with centered text |
| `:::features` | `pergament-block-features` | Feature grids and lists |
| `:::cta` | `pergament-block-cta` | Call-to-action sections |

You can use any name — it maps directly to a CSS class. Custom blocks like `:::pricing`, `:::testimonials`, or `:::team` will generate `pergament-block-pricing`, `pergament-block-testimonials`, and `pergament-block-team` classes respectively.

### Styling Blocks

Override the default styles by publishing the views and editing the CSS, or add your own styles targeting the generated classes:

```css
.pergament-block-hero {
    padding: 6rem 2rem;
    text-align: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.pergament-block-features {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    padding: 4rem 2rem;
}

.pergament-block-pricing {
    /* your custom block styles */
}
```

### Full Landing Page Example

Create `content/pages/home.md`:

```markdown
---
title: "My Product"
layout: landing
seo.title: "My Product - The Best Solution"
seo.description: "Discover the best solution for your needs"
---

:::hero

# My Product

Build something amazing with our platform.

[Get Started](/docs/getting-started/introduction) [Learn More](#features)

:::

:::features

## Features

### Lightning Fast
Our platform is optimized for performance at every level.

### Fully Extensible
Plugin system lets you customize everything.

### Dark Mode
Beautiful light and dark themes out of the box.

:::

:::cta

## Start Building Today

Join thousands of developers who trust our platform.

[Sign Up Free](/register)

:::
```

Then set the homepage config to use it:

```php
'homepage' => [
    'type' => 'page',
    'source' => 'home',
],
```

## Dark Mode

The package supports class-based dark mode. Add the `dark` class to your `<html>` element to activate dark mode. All views include `dark:` Tailwind variants.

### Themed Images

Documentation images can have dark/light variants that automatically switch based on the active theme. Place variant files alongside the original:

```
content/docs/0-getting-started/
├── 01-introduction.md
├── dashboard.png           # referenced in markdown
├── dashboard.dark.png      # shown in dark mode
└── dashboard.light.png     # shown in light mode (optional)
```

The variant resolution works as follows:

| Dark variant exists | Light variant exists | Light mode shows | Dark mode shows |
|---|---|---|---|
| No | No | `dashboard.png` | `dashboard.png` |
| Yes | No | `dashboard.png` | `dashboard.dark.png` |
| No | Yes | `dashboard.light.png` | `dashboard.png` |
| Yes | Yes | `dashboard.light.png` | `dashboard.dark.png` |

## Command Palette Search

When search is enabled, a command palette is available on every page. Open it with `Cmd+K` (macOS) or `Ctrl+K` (other platforms), or by clicking the search input in the navigation bar.

- **Real-time results** — results appear as you type, fetched from the search endpoint as JSON (no page reload)
- **Type badges** — each result is labelled **Doc**, **Post**, or **Page**
- **Keyboard navigation** — `↑`/`↓` to move between results, `Enter` to open, `Escape` to close
- **Mouse navigation** — click any result to navigate
- **Excerpt preview** — a short excerpt is shown below each title; falls back to the first 160 characters of content when no explicit excerpt is set in front matter
- **No-JS fallback** — the nav search form submits to `/search?q=…` as before when JavaScript is unavailable

Search covers all three content types:

| Type | Source |
|------|--------|
| Doc | Documentation pages |
| Post | Blog posts |
| Page | Standalone pages |

## Blade Components

The header and footer are extracted as anonymous Blade components so you can publish and customise them independently without overriding the entire view set.

### Header (`<x-pergament::header />`)

The header component renders the sticky navigation bar and includes:

- Site name / logo link
- Documentation and Blog navigation links (shown when the respective feature is enabled)
- Search input (shown when search is enabled)
- Font-size controls (A− / A+ / OpenDyslexic toggle)
- Dark mode toggle
- Mobile hamburger menu with all of the above
- Command palette overlay (shown when search is enabled)

Publish just the header to customise it:

```bash
php artisan vendor:publish --tag=pergament-header
```

This publishes `resources/views/vendor/pergament/components/header.blade.php` into your application. Laravel's view resolution automatically prefers the published file over the package default.

### Footer (`<x-pergament::footer />`)

The footer component renders the bottom bar containing the copyright notice.

Publish just the footer to customise it:

```bash
php artisan vendor:publish --tag=pergament-footer
```

This publishes `resources/views/vendor/pergament/components/footer.blade.php` into your application.

> Both components are also included when you run `php artisan vendor:publish --tag=pergament-views`.

## Artisan Commands

### Create a documentation page

We have an interactive docs creation command. All arguments are optional, you will be guided through all necessary things.

```bash
php artisan pergament:make:doc

# Or with arguments
php artisan pergament:make:doc --chapter=getting-started --title="Installation Guide" --order=02
```

### Create a blog post

```bash
php artisan pergament:make:post

# Or with arguments
php artisan pergament:make:post \
    --title="My First Post" \
    --category="Tutorials" \
    --tags="laravel, php" \
    --author="Jane Doe" \
    --date=2024-06-15
```

Both commands prompt for any missing arguments interactively.

## Routes

All routes are nested under the configured `prefix`. With the default `/` prefix:

| Route | Description |
|-------|-------------|
| `/` | Homepage |
| `/docs` | Documentation index (redirects to first page) |
| `/docs/{chapter}/{page}` | Documentation page |
| `/blog` | Blog index |
| `/blog/{slug}` | Blog post |
| `/blog/category/{category}` | Posts by category |
| `/blog/tag/{tag}` | Posts by tag |
| `/blog/author/{author}` | Posts by author |
| `/blog/feed` | RSS/Atom feed |
| `/search?q=query` | Search |
| `/{slug}` | Standalone page |
| `/sitemap.xml` | XML sitemap |
| `/robots.txt` | Robots.txt |
| `/llms.txt` | LLMs.txt |

With `prefix` set to `docs`, all routes become `/docs/...`, `/docs/blog/...`, etc.

## Analytics

Pergament includes a privacy-first, server-side analytics system. It records only the URL path, a timestamp, and whether the request came from a bot — no IP addresses, no cookies, no personal data, no third-party services. No cookie banner required.

Data is written as newline-delimited JSON (NDJSON) to one file per day:

```
storage/pergament/analytics/YYYY-MM-DD.ndjson
```

Each line is a JSON object:

```json
{"url":"/blog/my-post","timestamp":"2026-03-29T10:23:45+00:00","is_bot":false}
```

### Enabling analytics

Analytics is disabled by default. Enable it in your published config:

```php
'analytics' => [
    'enabled' => true,
    'storage_path' => null,  // defaults to storage/pergament/analytics
],
```

### Viewing analytics locally

```bash
# Today's page views (URL + type + count)
php artisan pergament:analytics

# A specific date
php artisan pergament:analytics --date=2026-03-01

# Multi-day summary
php artisan pergament:analytics --summary

# Configurable window
php artisan pergament:analytics --summary --days=7
```

Both modes display a `Users` and `Bots` breakdown. Bots are identified by their user-agent and always recorded — never silently dropped.

### Remote download endpoint

For production environments without shell access, you can expose a secure download URL that streams the raw NDJSON log for a given date.

**The endpoint is disabled by default.** A developer must explicitly enable it and set a secret token:

```php
'analytics' => [
    'enabled' => true,
    'download' => [
        'enabled' => true,
        'token'   => env('PERGAMENT_ANALYTICS_TOKEN'),
    ],
],
```

Generate a token:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Once enabled, the endpoint is available at:

```
GET {prefix}/analytics/download?date=YYYY-MM-DD&token=<your-token>
```

The `date` parameter defaults to today if omitted. The response is an `application/x-ndjson` file download.

| Response | Condition |
|----------|-----------|
| `404` | Download not enabled in config |
| `403` | Token missing, wrong, or not configured |
| `400` | Invalid date format |
| `404` | No data recorded for that date |
| `200` | NDJSON file download |

### Fetching analytics from a remote site

The `pergament:analytics` command can pull data from a remote site's download endpoint and display it locally — useful when you cannot SSH into production:

```bash
# Today's detail from remote
php artisan pergament:analytics \
    --remote=https://mysite.com \
    --token=<your-token>

# Specific date
php artisan pergament:analytics \
    --remote=https://mysite.com \
    --token=<your-token> \
    --date=2026-03-15

# 7-day summary from remote
php artisan pergament:analytics \
    --remote=https://mysite.com \
    --token=<your-token> \
    --summary \
    --days=7
```

The command fetches the NDJSON files from the remote endpoint and renders the same tables as local mode.

### Syncing remote data to local storage

If your production environment has no persistent file storage (ephemeral/serverless deployments), analytics data is lost on every deploy. Use `--sync` to download all recorded dates from the remote and merge them into your local storage before they disappear:

```bash
php artisan pergament:analytics \
    --remote=https://mysite.com \
    --token=<your-token> \
    --sync
```

The command:
1. Calls `GET /analytics/dates?token=…` to discover which dates have data on the remote
2. Downloads each date's NDJSON file
3. Merges entries into local storage — **duplicates are skipped** (matched by `url + timestamp`), so running `--sync` multiple times is safe
4. Displays today's stats from the now-merged local data

Combine with `--summary` or `--date` to control what is displayed after the sync:

```bash
# Sync everything, then show a 7-day summary
php artisan pergament:analytics \
    --remote=https://mysite.com \
    --token=<your-token> \
    --sync \
    --summary \
    --days=7
```

**Typical workflow for ephemeral environments:**

```bash
# 1. Before deploying a new version, sync production data locally
php artisan pergament:analytics --remote=https://mysite.com --token=… --sync

# 2. Deploy — production storage is wiped

# 3. View historical stats locally at any time (no remote needed)
php artisan pergament:analytics --summary --days=90
```

## Markdown Responses for AI & LLMs

All content pages (documentation, blog posts, standalone pages, and the homepage) can be served as plain Markdown instead of HTML. This is configurable in the exports section of the configuration.

A markdown response is returned when any of the following is true:

| Trigger | Example |
|---------|---------|
| `Accept: text/markdown` request header | `curl -H "Accept: text/markdown" /docs/getting-started/installation` |
| Known AI / LLM user-agent | Requests from ChatGPT, Claude, Perplexity, etc. |
| `.md` URL suffix | `/blog/my-post.md` |

Media files, feeds, sitemaps, and search results are excluded — only rendered HTML content pages are converted.

## Features

- **File-based content** — Markdown + YAML front matter, no database
- **Documentation** — Numbered chapters/pages, sidebar navigation, TOC scrollspy, heading anchor links, themed images
- **Blog** — Categories, tags, multiple authors, date-prefixed directories, pagination
- **RSS/Atom feeds** — Configurable feed type and limits
- **SEO** — Meta tags, Open Graph, Twitter Cards, per-page overrides via dot notation
- **Sitemap** — Auto-generated XML sitemap
- **robots.txt / llms.txt** — Auto-generated or custom content
- **Markdown responses** — All content pages served as plain Markdown via `Accept: text/markdown`, `.md` suffix, or known AI user-agents (powered by spatie/laravel-markdown-response)
- **Command palette search** — `Cmd+K`/`Ctrl+K` opens a live search dialog across docs, posts, and pages; keyboard navigable; no-JS form fallback
- **PWA** — Optional manifest.json and service worker
- **GitHub-style alerts** — `> [!NOTE]`, `> [!TIP]`, `> [!IMPORTANT]`, `> [!WARNING]`, `> [!CAUTION]` blocks with icons and color coding; dark mode included
- **Footnotes** — Inline `[^ref]` syntax with superscript links and back-references; opt-in via config
- **Downloads** — Relative file links in blog posts and docs are auto-rewritten to media URLs; `:::download` block directive adds the HTML `download` attribute to prompt browser saves
- **Landing pages** — Block-based content with `:::directive` syntax
- **Dark mode** — Class-based toggle with system preference detection; dark-mode syntax highlighting
- **Syntax highlighting** — Server-side via tempest/highlight, light and dark themes included
- **Theming** — Configure `colors.primary` and `colors.background`; the entire UI (nav, links, badges, scrollbars, focus rings, text selection) derives from these two values via CSS custom properties
- **Zoomable images** — Click any image to enlarge it in a lightbox; Escape or click outside to close
- **Copy code** — Hover a code block to reveal a Copy button; switches to "Copied" on success
- **Text-to-speech** — Optional play/pause button that reads content aloud using the browser Speech Synthesis API; configurable per content type, with selectable voice and speech rate
- **Analytics** — Privacy-first page view tracking (URL + timestamp + bot flag), NDJSON storage, Artisan command with local and remote display, optional token-protected download endpoint
- **Configurable prefix** — Mount the CMS at any URL path

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](./.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Robert Kummer](https://github.com/rokde)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
