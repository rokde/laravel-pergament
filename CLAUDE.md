# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Is

Laravel Pergament — a file-based CMS package for Laravel. No database. Renders documentation, blog posts, and standalone pages from Markdown files with YAML front matter. Supports SEO, RSS/Atom feeds, sitemaps, PWA, syntax highlighting, search, dark mode image variants, and static site generation.

## Commands

```bash
composer install              # Install dependencies
composer test                 # Run lint + unit tests
composer test:lint             # Laravel Pint linting (parallel)
composer test:unit             # Pest tests with coverage (min 80%)
composer lint                  # Auto-fix code style

# Run a single test file
./vendor/bin/pest tests/Feature/BlogServiceTest.php

# Run a single test by name
./vendor/bin/pest --filter="returns all posts sorted by date"
```

## Architecture

**Request flow:** Route → Controller → Service → Data Object → Blade View

### Key layers

- **`src/Data/`** — Immutable `final readonly` DTOs (BlogPost, DocPage, Page, Author, SeoMeta, etc.)
- **`src/Services/`** — Business logic. Services read the filesystem via FrontMatterParser, convert Markdown via MarkdownRenderer, return DTOs. Key services: BlogService, DocumentationService, PageService, SearchService, SeoService, FeedService, SitemapService
- **`src/Http/Controllers/`** — Thin controllers that delegate to services and return views
- **`src/Support/`** — FrontMatterParser (custom YAML parser with dot notation), SyntaxHighlighter, UrlGenerator
- **`src/Config/pergament.php`** — All configuration (content path, URL prefix, site settings, feature flags)
- **`routes/web.php`** — All route definitions, conditionally registered based on config feature flags

### Service Provider

`PergamentServiceProvider` merges config, registers views under `pergament::` namespace, loads routes, and registers artisan commands. Auto-discovered via composer extra.

### Content file conventions

- **Docs:** `content/docs/{order}-{chapter-slug}/{order}-{page-slug}.md` — numeric prefixes control ordering, stripped from URLs
- **Blog:** `content/blog/{YYYY-MM-DD}-{slug}/post.md` — date from directory name, media files alongside post.md
- **Pages:** `content/pages/{slug}.md` — filename becomes URL slug

### Testing

- Pest PHP with Orchestra Testbench for package testing
- All tests in `tests/Feature/`
- TestCase sets `pergament.content_path` to `tests/fixtures/content/`
- Fixtures provide sample docs, blog posts, and pages for testing

## Code Conventions

- PHP 8.4+, `declare(strict_types=1)` in all files
- Data objects are `final readonly` with typed constructor properties
- PSR-12 via Laravel Pint
- 4-space indentation (2 for YAML), LF line endings
- Namespace: `Pergament\` (src), `Pergament\Tests\` (tests)