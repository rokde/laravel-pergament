## Laravel Pergament

A file-based CMS package for Laravel. No database required. Renders documentation, blog posts, and standalone pages from Markdown files with YAML front matter.

### Features

- **Documentation**: Structured docs with chapters, ordered pages, sidebar navigation, and previous/next links
- **Blog**: Posts with categories, tags, authors, pagination, and RSS/Atom feeds
- **Pages**: Standalone pages with optional landing page layout using block directives
- **SEO**: Automatic meta tags, Open Graph, Twitter Cards — overridable per page via front matter dot notation
- **Search**: Full-text search across all content types
- **Sitemap**: Auto-generated XML sitemap
- **robots.txt & llms.txt**: Auto-generated, customizable
- **PWA**: Optional manifest.json and service worker
- **Dark mode**: Built-in toggle with themed image variants for docs
- **Syntax highlighting**: Server-side code block highlighting via Tempest
- **Static site generation**: Export entire site as static HTML

### Content Structure

All content lives under the path configured in `pergament.content_path` (default: `content/`):

- `content/docs/{order}-{chapter-slug}/{order}-{page-slug}.md` — numeric prefixes for ordering, stripped from URLs
- `content/blog/{YYYY-MM-DD}-{slug}/post.md` — date from directory name, media files alongside
- `content/pages/{slug}.md` — filename becomes URL slug

### Front Matter

Standard YAML front matter with `title`, `excerpt`, and content-type-specific fields:

@verbatim
<code-snippet name="Blog post front matter" lang="yaml">
---
title: "My Post Title"
excerpt: "A brief summary"
category: "Tutorials"
tags:
  - "laravel"
  - "php"
author: "Jane Doe"
seo.title: "Custom SEO Title"
seo.description: "Custom meta description"
seo.og_image: "https://example.com/og.png"
---
</code-snippet>
@endverbatim

SEO fields use dot notation (e.g. `seo.title`) to override global defaults from config.

### Block Directives

Pages support block directives for structured layouts (used with `layout: landing` in front matter):

@verbatim
<code-snippet name="Block directive syntax" lang="markdown">
:::hero

# Welcome to My Site

:::

:::features

## Key Features

- Fast and lightweight
- No database needed

:::
</code-snippet>
@endverbatim

These render as `<div class="pergament-block pergament-block-{name}">` elements.

### Configuration

Publish the config file to customize all features:

@verbatim
<code-snippet name="Publish config" lang="bash">
php artisan vendor:publish --tag=pergament-config
</code-snippet>
@endverbatim

Key config options in `config/pergament.php`:

- `content_path` — base directory for all content files
- `prefix` — URL prefix for all Pergament routes (use `/` for root)
- `site.name`, `site.url`, `site.seo.*` — global site and SEO settings
- `homepage.type` — what serves at the base URL: `page`, `blog-index`, `doc-page`, or `redirect`
- `docs.enabled`, `blog.enabled`, `pages.enabled` — toggle content types
- `blog.per_page`, `blog.feed.*` — blog pagination and feed settings
- `pwa.enabled`, `pwa.*` — PWA configuration

### Customizing Views

Publish views to override any template:

@verbatim
<code-snippet name="Publish views" lang="bash">
php artisan vendor:publish --tag=pergament-views
</code-snippet>
@endverbatim

Published views go to `resources/views/vendor/pergament/`. Key templates:

- `layouts/app.blade.php` — main layout (navigation, footer, dark mode, Tailwind CSS)
- `layouts/docs.blade.php` — documentation layout with sidebar
- `blog/show.blade.php` — single blog post
- `blog/index.blade.php` — blog listing
- `docs/show.blade.php` — documentation page
- `pages/show.blade.php` — standalone page (standard + landing layout)
- `components/seo-head.blade.php` — SEO meta tags component
- `components/post-card.blade.php` — blog post card component

Views use the `pergament::` namespace. Components use `<x-pergament::component-name>`.

### Artisan Commands

@verbatim
<code-snippet name="Available commands" lang="bash">
php artisan pergament:make-blog-post {slug}       # Create a new blog post
php artisan pergament:make-doc-page {chapter} {page}  # Create a new doc page
php artisan pergament:generate-static {output-dir}    # Export static HTML site
</code-snippet>
@endverbatim

### Routes

All routes are prefixed with `pergament.` and conditionally registered based on config feature flags. Key named routes:

- `pergament.home` — homepage
- `pergament.docs.index`, `pergament.docs.show` — documentation
- `pergament.blog.index`, `pergament.blog.show`, `pergament.blog.category`, `pergament.blog.tag`, `pergament.blog.author` — blog
- `pergament.blog.feed` — RSS/Atom feed
- `pergament.search` — search
- `pergament.sitemap` — sitemap
- `pergament.page` — standalone pages (catch-all, registered last)

### Architecture

Request flow: **Route -> Controller -> Service -> Data Object -> Blade View**

Services (`Pergament\Services\*`) encapsulate all business logic. Controllers are thin. Data objects (`Pergament\Data\*`) are `final readonly` DTOs. The `FrontMatterParser` handles YAML parsing with dot notation support. The `MarkdownRenderer` converts Markdown to HTML with syntax highlighting, heading IDs, block directives, and content link resolution.
