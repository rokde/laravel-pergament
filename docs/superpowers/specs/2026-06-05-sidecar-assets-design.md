# Sidecar CSS/JS Assets — Design

## Goal

Allow content authors to attach page-scoped CSS and JS by placing a same-named
`.css` and/or `.js` file next to a Markdown file. The file contents are delivered
inline with the rendered page: CSS into `@stack('styles')`, JS into
`@stack('scripts')`. Works identically for runtime URL requests and static site
generation.

## File Conventions

The sidecar files share the Markdown file's basename, in the same directory:

| Content type | Markdown file                                | Sidecar files                          |
|--------------|----------------------------------------------|----------------------------------------|
| Page         | `content/pages/home.md`                       | `content/pages/home.css`, `home.js`    |
| Blog post    | `content/blog/2024-01-15-hello/post.md`       | same dir: `post.css`, `post.js`        |
| Doc page     | `content/docs/0-getting-started/0-intro.md`   | same dir: `0-intro.css`, `0-intro.js`  |

Exactly one `.css` and one `.js` per Markdown file. Both are optional and
independent — a page may have only CSS, only JS, both, or neither.

## Delivery: Inline

The sidecar contents are embedded directly into the page output:

```blade
@push('styles')
<style>{!! $page['styles'] !!}</style>
@endpush

@push('scripts')
<script>{!! $page['scripts'] !!}</script>
@endpush
```

The layout (`layouts/app.blade.php`) already renders `@stack('styles')` in
`<head>` and `@stack('scripts')` before the bundled JS at end of `<body>`. Blade
`@push` propagates across `@extends`, so docs pages (which extend
`layouts.docs` → `layouts.app`) work too.

Inline was chosen over separate asset files because it requires no new routes, no
path rewriting, and the static generator needs no special handling — it renders
the same views, so inline `<style>`/`<script>` come along automatically.

## Components

### `src/Support/SidecarAssets.php` (new)

```php
final readonly class SidecarAssets
{
    /** @return array{styles: ?string, scripts: ?string} */
    public function forMarkdownFile(string $mdPath): array;
}
```

- Derives sidecar paths by replacing the trailing `.md` with `.css` / `.js`.
- Reads each file if it exists; returns trimmed contents or `null`.
- Pure filesystem read; no parsing, no escaping.

### Service changes

Each rendered-page array gains two keys: `styles` and `scripts` (both `?string`).

- `PageService::getRenderedPage` — md path is `$sourceFile` (`pages/{slug}.md`).
- `BlogService::getRenderedPost` — md path via existing `resolveSourceFilePath($slug)`
  (`{date-slug}/post.md` → `post.css` / `post.js`).
- `DocumentationService::getRenderedPage` — md path via existing
  `resolveSourceFilePath($chapterSlug, $pageSlug)`.

When the source file path is `null` (already-handled edge case), both keys are
`null`.

### View changes

Add guarded push blocks to:

- `resources/views/pages/show.blade.php` (`$page['styles']` / `$page['scripts']`)
- `resources/views/blog/show.blade.php` (`$post['styles']` / `$post['scripts']`)
- `resources/views/docs/show.blade.php` (`$page['styles']` / `$page['scripts']`)

Guard with `@if` so no empty `<style>`/`<script>` tag is emitted when the sidecar
is absent.

### Static generation

No changes to `GenerateStaticCommand`. It renders the same views, so inline assets
are included in the generated HTML automatically.

## Decisions

- **Raw injection.** Sidecar contents are injected unescaped (`{!! !!}`). This is
  deliberate — authors are writing CSS/JS for their own site. No sanitization.
  A `</style>` or `</script>` literal in the sidecar would break the tag; that is
  the author's responsibility. Documented as such.
- **DTOs untouched.** `Page`, `BlogPost`, `DocPage` stay clean. `styles`/`scripts`
  live only in the rendered array (a rendering concern), matching how
  `htmlContent`, `headings`, `statistics` are handled.
- **No config flag.** Feature is always on; presence of a sidecar file is the
  opt-in. No new `pergament.php` config.

## Testing

- Fixtures: add sidecar `.css`/`.js` files for one page, one blog post, one doc
  page under `tests/fixtures/content/`.
- Feature tests: assert rendered response contains the inline `<style>`/`<script>`
  with the sidecar content, for pages, blog, docs.
- Negative test: a page without sidecars emits no empty `<style>`/`<script>` tag.
- Static test: run `pergament:generate-static`, assert the generated HTML for a
  page with sidecars contains the inline content.
- `SidecarAssets` unit coverage via the service/feature tests (returns content
  when present, `null` when absent).

## Documentation

Add a docs page describing the behavior: naming convention per content type, the
inline-delivery mechanism, that contents are injected raw, and the one-css/one-js
rule.
