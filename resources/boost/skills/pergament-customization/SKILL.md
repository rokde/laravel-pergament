---
name: pergament-customization
description: Customize and extend Laravel Pergament — publish and override views, configure features, style templates, and generate static sites.
---

# Pergament Customization

## When to Activate

- When asked to customize the look and feel of a Pergament site
- When asked to modify layouts, templates, or components
- When configuring Pergament features (blog, docs, SEO, PWA, etc.)
- When generating a static site or setting up deployment

## Publishing Assets

### Publish Configuration

```bash
php artisan vendor:publish --tag=pergament-config
```

Creates `config/pergament.php` where you can customize all settings.

### Publish Views

```bash
php artisan vendor:publish --tag=pergament-views
```

Copies all Blade templates to `resources/views/vendor/pergament/` for customization. Laravel automatically uses these over the package defaults.

## View Architecture

All views use the `pergament::` namespace. After publishing, edit them at `resources/views/vendor/pergament/`.

### Layouts

**`layouts/app.blade.php`** — Main layout used by blog and pages:
- Navigation bar with site name, doc/blog links, search form, dark mode toggle
- Mobile responsive hamburger menu
- Footer with copyright
- Uses Tailwind CSS via CDN and Tempest highlight CSS
- Sections: `@yield('seo')`, `@yield('content')`
- Stacks: `@stack('styles')`, `@stack('scripts')`

**`layouts/docs.blade.php`** — Documentation layout:
- Extends `layouts/app.blade.php`
- Adds sidebar navigation with chapters and pages
- Table of contents from page headings
- Section: `@yield('docs-content')`

### Content Templates

**`blog/index.blade.php`** — Blog listing page
- Receives: `$posts` (Collection of BlogPost), `$currentPage`, `$lastPage`, `$total`, `$seo`

**`blog/show.blade.php`** — Single blog post
- Receives: `$post` (array with keys: `title`, `excerpt`, `htmlContent`, `headings`, `slug`, `date`, `category`, `tags`, `authors`, `meta`, `previousPost`, `nextPost`), `$seo`

**`blog/category.blade.php`** — Posts filtered by category
- Receives: `$posts`, `$category`, `$categorySlug`, `$seo`

**`blog/tag.blade.php`** — Posts filtered by tag
- Receives: `$posts`, `$tag`, `$tagSlug`, `$seo`

**`blog/author.blade.php`** — Posts filtered by author
- Receives: `$posts`, `$author`, `$authorSlug`, `$seo`

**`docs/show.blade.php`** — Documentation page
- Receives: `$page` (array with keys: `title`, `excerpt`, `htmlContent`, `headings`, `slug`, `meta`, `previousPage`, `nextPage`), `$navigation`, `$currentChapter`, `$currentPage`, `$seo`

**`pages/show.blade.php`** — Standalone page
- Receives: `$page` (array with keys: `title`, `excerpt`, `htmlContent`, `headings`, `slug`, `layout`, `meta`), `$seo`, `$layout`, `$isHomepage`

### Components

**`<x-pergament::seo-head :seo="$seo" />`** — Renders meta tags, Open Graph, Twitter Cards
**`<x-pergament::post-card :post="$post" />`** — Blog post card for listings

## Configuration Reference

### Site Settings

```php
'site' => [
    'name' => env('APP_NAME'),
    'url' => env('APP_URL'),
    'locale' => 'en',
    'seo' => [
        'title' => env('APP_NAME'),
        'description' => '',
        'keywords' => '',
        'og_image' => '',
        'twitter_card' => 'summary_large_image',
        'robots' => 'index, follow',
    ],
],
```

### Homepage

```php
'homepage' => [
    'type' => 'page',      // page | blog-index | doc-page | redirect
    'source' => 'home',    // slug, chapter/page, or URL depending on type
],
```

### Feature Toggles

```php
'docs' => ['enabled' => true, 'url_prefix' => 'docs'],
'blog' => ['enabled' => true, 'url_prefix' => 'blog', 'per_page' => 12],
'pages' => ['enabled' => true],
'search' => ['enabled' => true],
'sitemap' => ['enabled' => true],
'robots' => ['enabled' => true],
'llms' => ['enabled' => true],
'pwa' => ['enabled' => false],
```

### Blog Feed

```php
'blog' => [
    'feed' => [
        'enabled' => true,
        'type' => 'atom',       // atom | rss
        'title' => null,        // defaults to site name + " Feed"
        'description' => '',
        'limit' => 20,
    ],
    'default_authors' => [],    // fallback authors when post has none
],
```

### PWA

```php
'pwa' => [
    'enabled' => false,
    'name' => env('APP_NAME'),
    'short_name' => env('APP_NAME'),
    'description' => '',
    'theme_color' => '#ffffff',
    'background_color' => '#ffffff',
    'display' => 'standalone',
    'icons' => [],
],
```

## Styling

The default templates use Tailwind CSS (CDN) and can be fully replaced after publishing views. Key CSS classes used by the renderer:

- `.pergament-code-block` — syntax-highlighted code blocks (has `data-language` attribute)
- `.pergament-block` — block directive container
- `.pergament-block-{name}` — specific block directive (e.g., `.pergament-block-hero`)
- `.pergament-img-light` / `.pergament-img-dark` — themed image variants
- `.prose` / `.dark:prose-invert` — Tailwind Typography for rendered Markdown content

## Static Site Generation

Export the entire site as static HTML:

```bash
# Basic export
php artisan pergament:generate-static ./dist

# With options
php artisan pergament:generate-static ./dist \
  --clean \
  --prefix="/my-site" \
  --base-url="https://example.com"
```

Options:
- `--clean` — remove output directory before generating
- `--prefix` — override URL prefix for the export
- `--base-url` — override site URL for sitemap and feed links

The command generates HTML files, copies media, creates sitemap.xml, robots.txt, llms.txt, and feed XML. Pagination links are rewritten from `?page=N` to `/page/N/` for static hosting.

## Named Routes

Use these for generating URLs in custom views:

```php
route('pergament.home')
route('pergament.docs.index')
route('pergament.docs.show', ['chapter' => 'getting-started', 'page' => 'installation'])
route('pergament.blog.index')
route('pergament.blog.show', ['slug' => 'my-post'])
route('pergament.blog.category', ['category' => 'tutorials'])
route('pergament.blog.tag', ['tag' => 'laravel'])
route('pergament.blog.author', ['author' => 'jane-doe'])
route('pergament.blog.feed')
route('pergament.search')
route('pergament.sitemap')
route('pergament.page', ['slug' => 'about'])
```

## Do and Don't

Do:
- Publish views before customizing — edit published copies, not package files
- Use `@yield`, `@stack`, and `@section` from the existing layouts when extending
- Use named routes (`route('pergament.blog.show', ...)`) instead of hardcoding URLs
- Test with `php artisan pergament:generate-static` to verify all pages render correctly

Don't:
- Edit files directly in `vendor/` — always publish first
- Remove the `@yield('seo')` section from layouts — it provides SEO meta tags
- Forget to re-publish views after package updates if you want new template features
