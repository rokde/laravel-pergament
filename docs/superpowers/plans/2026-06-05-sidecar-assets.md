# Sidecar CSS/JS Assets Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a same-named `.css`/`.js` file placed next to a Markdown file inline with the rendered page — CSS into `@stack('styles')`, JS into `@stack('scripts')` — for both runtime requests and static generation.

**Architecture:** A stateless `SidecarAssets` static helper derives sibling `.css`/`.js` paths from a Markdown path and returns their contents (or `null`). The three rendering services (`PageService`, `BlogService`, `DocumentationService`) add `styles`/`scripts` keys to their rendered-page arrays. The three `show` Blade views push those values inline. Static generation needs no change — it renders the same views.

**Tech Stack:** PHP 8.4, Laravel package, Blade, Pest + Orchestra Testbench.

---

## File Structure

- **Create** `src/Support/SidecarAssets.php` — static helper, reads sibling sidecar files.
- **Create** `tests/Feature/SidecarAssetsTest.php` — unit tests for the helper.
- **Modify** `src/Services/PageService.php` — add `styles`/`scripts` to rendered array.
- **Modify** `src/Services/BlogService.php` — add `styles`/`scripts` to rendered array.
- **Modify** `src/Services/DocumentationService.php` — add `styles`/`scripts` to rendered array.
- **Modify** `resources/views/pages/show.blade.php` — push inline assets.
- **Modify** `resources/views/blog/show.blade.php` — push inline assets.
- **Modify** `resources/views/docs/show.blade.php` — push inline assets.
- **Modify** existing feature tests / fixtures for page, blog, doc, static.
- **Create** `content/docs/<chapter>/<page>.md` — documentation page.

---

## Task 1: `SidecarAssets` helper

**Files:**
- Create: `src/Support/SidecarAssets.php`
- Test: `tests/Feature/SidecarAssetsTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/SidecarAssetsTest.php`:

```php
<?php

declare(strict_types=1);

use Pergament\Support\SidecarAssets;

it('returns css and js contents for a markdown file', function () {
    $dir = sys_get_temp_dir().'/pergament-sidecar-'.uniqid();
    mkdir($dir);
    file_put_contents($dir.'/home.md', '# Home');
    file_put_contents($dir.'/home.css', '.hero { color: red; }');
    file_put_contents($dir.'/home.js', "console.log('hi');");

    $result = SidecarAssets::forMarkdownFile($dir.'/home.md');

    expect($result['styles'])->toBe('.hero { color: red; }')
        ->and($result['scripts'])->toBe("console.log('hi');");
});

it('returns null for missing sidecars', function () {
    $dir = sys_get_temp_dir().'/pergament-sidecar-'.uniqid();
    mkdir($dir);
    file_put_contents($dir.'/home.md', '# Home');

    $result = SidecarAssets::forMarkdownFile($dir.'/home.md');

    expect($result['styles'])->toBeNull()
        ->and($result['scripts'])->toBeNull();
});

it('returns nulls for a null path', function () {
    $result = SidecarAssets::forMarkdownFile(null);

    expect($result['styles'])->toBeNull()
        ->and($result['scripts'])->toBeNull();
});

it('trims sidecar contents', function () {
    $dir = sys_get_temp_dir().'/pergament-sidecar-'.uniqid();
    mkdir($dir);
    file_put_contents($dir.'/home.md', '# Home');
    file_put_contents($dir.'/home.css', "\n.hero {}\n\n");

    $result = SidecarAssets::forMarkdownFile($dir.'/home.md');

    expect($result['styles'])->toBe('.hero {}');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/SidecarAssetsTest.php`
Expected: FAIL — `Class "Pergament\Support\SidecarAssets" not found`.

- [ ] **Step 3: Write minimal implementation**

Create `src/Support/SidecarAssets.php`:

```php
<?php

declare(strict_types=1);

namespace Pergament\Support;

final class SidecarAssets
{
    /**
     * Read the same-named `.css` and `.js` files sitting next to a Markdown file.
     *
     * @return array{styles: ?string, scripts: ?string}
     */
    public static function forMarkdownFile(?string $mdPath): array
    {
        if ($mdPath === null || ! str_ends_with($mdPath, '.md')) {
            return ['styles' => null, 'scripts' => null];
        }

        $base = substr($mdPath, 0, -3);

        return [
            'styles' => self::read($base.'.css'),
            'scripts' => self::read($base.'.js'),
        ];
    }

    private static function read(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $trimmed = trim($contents);

        return $trimmed === '' ? null : $trimmed;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Feature/SidecarAssetsTest.php`
Expected: PASS (4 passed).

- [ ] **Step 5: Commit**

```bash
git add src/Support/SidecarAssets.php tests/Feature/SidecarAssetsTest.php
git commit -m "feat: add SidecarAssets helper for sibling css/js files"
```

---

## Task 2: PageService + page view

**Files:**
- Modify: `src/Services/PageService.php:72-83` (rendered array)
- Modify: `resources/views/pages/show.blade.php`
- Test: `tests/Feature/PageServiceTest.php`, fixtures under `tests/fixtures/content/pages/`

- [ ] **Step 1: Add fixtures**

Create `tests/fixtures/content/pages/about.css` with:

```css
.about-hero { color: rebeccapurple; }
```

Create `tests/fixtures/content/pages/about.js` with:

```js
console.log('about page');
```

(`about.md` already exists in fixtures.)

- [ ] **Step 2: Write the failing test**

Add to `tests/Feature/PageServiceTest.php`:

```php
it('includes sidecar styles and scripts in the rendered page', function () {
    $page = app(\Pergament\Services\PageService::class)->getRenderedPage('about');

    expect($page['styles'])->toBe('.about-hero { color: rebeccapurple; }')
        ->and($page['scripts'])->toBe("console.log('about page');");
});

it('has null sidecar keys when no sidecar files exist', function () {
    $page = app(\Pergament\Services\PageService::class)->getRenderedPage('home');

    expect($page['styles'])->toBeNull()
        ->and($page['scripts'])->toBeNull();
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/PageServiceTest.php`
Expected: FAIL — undefined array key `styles`.

- [ ] **Step 4: Implement in service**

In `src/Services/PageService.php`, inside `getRenderedPage`, after the `$contentStats` line (around line 70) add:

```php
        $sidecar = \Pergament\Support\SidecarAssets::forMarkdownFile($sourceFile);
```

Then in the returned array (around line 72-83), add two keys before `'linkErrors'`:

```php
            'styles' => $sidecar['styles'],
            'scripts' => $sidecar['scripts'],
```

- [ ] **Step 5: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Feature/PageServiceTest.php`
Expected: PASS.

- [ ] **Step 6: Push assets in the page view**

In `resources/views/pages/show.blade.php`, immediately after `@section('content')` opens its content but before the existing `@push('styles')` landing block at the end, add (place right before the final `@endsection`, after the existing landing `@push('styles')...@endpush` block at lines 76-90):

```blade
@if(!empty($page['styles']))
@push('styles')
<style>{!! $page['styles'] !!}</style>
@endpush
@endif

@if(!empty($page['scripts']))
@push('scripts')
<script>{!! $page['scripts'] !!}</script>
@endpush
@endif
```

- [ ] **Step 7: Write the view feature test**

Add to `tests/Feature/PageServiceTest.php` (or a controller test if one exists — PageService test bootstraps the package, so use a route call):

```php
it('renders sidecar css and js inline on the page response', function () {
    $response = $this->get('/about');

    $response->assertOk()
        ->assertSee('<style>.about-hero { color: rebeccapurple; }</style>', false)
        ->assertSee("<script>console.log('about page');</script>", false);
});

it('does not emit empty style or script tags without sidecars', function () {
    $response = $this->get('/home');

    $response->assertOk()
        ->assertDontSee('<style></style>', false)
        ->assertDontSee('<script></script>', false);
});
```

- [ ] **Step 8: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Feature/PageServiceTest.php`
Expected: PASS. If `/about` or `/home` route differs, confirm the page URL prefix via `config('pergament.prefix')` and adjust the path.

- [ ] **Step 9: Commit**

```bash
git add src/Services/PageService.php resources/views/pages/show.blade.php tests/Feature/PageServiceTest.php tests/fixtures/content/pages/about.css tests/fixtures/content/pages/about.js
git commit -m "feat: deliver sidecar css/js inline for standalone pages"
```

---

## Task 3: BlogService + blog view

**Files:**
- Modify: `src/Services/BlogService.php:90-` (rendered array)
- Modify: `resources/views/blog/show.blade.php`
- Test: `tests/Feature/BlogServiceTest.php`, fixtures under `tests/fixtures/content/blog/2024-01-15-hello-world/`

- [ ] **Step 1: Add fixtures**

Create `tests/fixtures/content/blog/2024-01-15-hello-world/post.css` with:

```css
.post-callout { border: 1px solid teal; }
```

Create `tests/fixtures/content/blog/2024-01-15-hello-world/post.js` with:

```js
console.log('hello world post');
```

- [ ] **Step 2: Write the failing test**

Add to `tests/Feature/BlogServiceTest.php`:

```php
it('includes sidecar styles and scripts in the rendered post', function () {
    $post = app(\Pergament\Services\BlogService::class)->getRenderedPost('hello-world');

    expect($post['styles'])->toBe('.post-callout { border: 1px solid teal; }')
        ->and($post['scripts'])->toBe("console.log('hello world post');");
});
```

(Confirm the post slug is `hello-world`; the directory is `2024-01-15-hello-world` and the date prefix is stripped.)

- [ ] **Step 3: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/BlogServiceTest.php`
Expected: FAIL — undefined array key `styles`.

- [ ] **Step 4: Implement in service**

In `src/Services/BlogService.php`, inside `getRenderedPost`, after the `$contentStats` line (around line 90) add:

```php
        $sidecar = \Pergament\Support\SidecarAssets::forMarkdownFile($sourceFile);
```

Then add to the returned array (around line 92-103), before `'previousPost'`:

```php
            'styles' => $sidecar['styles'],
            'scripts' => $sidecar['scripts'],
```

- [ ] **Step 5: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Feature/BlogServiceTest.php`
Expected: PASS.

- [ ] **Step 6: Push assets in the blog view**

In `resources/views/blog/show.blade.php`, before the final `@endsection` (after the closing `</article>` / nav block near line 114-116), add:

```blade
@if(!empty($post['styles']))
@push('styles')
<style>{!! $post['styles'] !!}</style>
@endpush
@endif

@if(!empty($post['scripts']))
@push('scripts')
<script>{!! $post['scripts'] !!}</script>
@endpush
@endif
```

- [ ] **Step 7: Write the view feature test**

Add to `tests/Feature/BlogControllerTest.php`:

```php
it('renders sidecar css and js inline on the blog post response', function () {
    $response = $this->get('/blog/hello-world');

    $response->assertOk()
        ->assertSee('<style>.post-callout { border: 1px solid teal; }</style>', false)
        ->assertSee("<script>console.log('hello world post');</script>", false);
});
```

(Confirm the blog URL via `config('pergament.blog.url_prefix', 'blog')`.)

- [ ] **Step 8: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Feature/BlogControllerTest.php`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add src/Services/BlogService.php resources/views/blog/show.blade.php tests/Feature/BlogServiceTest.php tests/Feature/BlogControllerTest.php tests/fixtures/content/blog/2024-01-15-hello-world/post.css tests/fixtures/content/blog/2024-01-15-hello-world/post.js
git commit -m "feat: deliver sidecar css/js inline for blog posts"
```

---

## Task 4: DocumentationService + docs view

**Files:**
- Modify: `src/Services/DocumentationService.php:113-131` (rendered array)
- Modify: `resources/views/docs/show.blade.php`
- Test: `tests/Feature/DocumentationServiceTest.php`, fixtures under `tests/fixtures/content/docs/0-getting-started/`

- [ ] **Step 1: Add fixtures**

Create `tests/fixtures/content/docs/0-getting-started/01-introduction.css` with:

```css
.doc-note { background: lightyellow; }
```

Create `tests/fixtures/content/docs/0-getting-started/01-introduction.js` with:

```js
console.log('intro doc');
```

(`01-introduction.md` already exists; its slug is `introduction`, chapter slug `getting-started`.)

- [ ] **Step 2: Write the failing test**

Add to `tests/Feature/DocumentationServiceTest.php`:

```php
it('includes sidecar styles and scripts in the rendered doc page', function () {
    $page = app(\Pergament\Services\DocumentationService::class)
        ->getRenderedPage('getting-started', 'introduction');

    expect($page['styles'])->toBe('.doc-note { background: lightyellow; }')
        ->and($page['scripts'])->toBe("console.log('intro doc');");
});
```

(Confirm chapter/page slugs against existing tests in this file before running.)

- [ ] **Step 3: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/DocumentationServiceTest.php`
Expected: FAIL — undefined array key `styles`.

- [ ] **Step 4: Implement in service**

In `src/Services/DocumentationService.php`, inside `getRenderedPage`, after the `$contentStats` line (around line 113) add:

```php
        $sidecar = \Pergament\Support\SidecarAssets::forMarkdownFile($sourceFile);
```

Then add to the returned array (around line 115-131), before `'linkErrors'`:

```php
            'styles' => $sidecar['styles'],
            'scripts' => $sidecar['scripts'],
```

- [ ] **Step 5: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Feature/DocumentationServiceTest.php`
Expected: PASS.

- [ ] **Step 6: Push assets in the docs view**

In `resources/views/docs/show.blade.php`, before the final `@endsection` (after `</article>` at line 53), add:

```blade
@if(!empty($page['styles']))
@push('styles')
<style>{!! $page['styles'] !!}</style>
@endpush
@endif

@if(!empty($page['scripts']))
@push('scripts')
<script>{!! $page['scripts'] !!}</script>
@endpush
@endif
```

- [ ] **Step 7: Write the view feature test**

Add to `tests/Feature/DocumentationControllerTest.php`:

```php
it('renders sidecar css and js inline on the doc page response', function () {
    $response = $this->get('/docs/getting-started/introduction');

    $response->assertOk()
        ->assertSee('<style>.doc-note { background: lightyellow; }</style>', false)
        ->assertSee("<script>console.log('intro doc');</script>", false);
});
```

(Confirm the docs URL via `config('pergament.docs.url_prefix', 'docs')` and the slugs used by existing tests in this file.)

- [ ] **Step 8: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Feature/DocumentationControllerTest.php`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add src/Services/DocumentationService.php resources/views/docs/show.blade.php tests/Feature/DocumentationServiceTest.php tests/Feature/DocumentationControllerTest.php tests/fixtures/content/docs/0-getting-started/01-introduction.css tests/fixtures/content/docs/0-getting-started/01-introduction.js
git commit -m "feat: deliver sidecar css/js inline for doc pages"
```

---

## Task 5: Static generation coverage

**Files:**
- Test: `tests/Feature/GenerateStaticCommandTest.php`

No production code change — the static command renders the same views, so inline
assets are already emitted. This task only adds a regression test.

- [ ] **Step 1: Write the failing-or-passing test**

Add to `tests/Feature/GenerateStaticCommandTest.php` (match the existing test
style in that file — it generates to a temp dir and reads output files):

```php
it('embeds sidecar css and js inline in static output', function () {
    $outputDir = sys_get_temp_dir().'/pergament-static-'.uniqid();

    $this->artisan('pergament:generate-static', ['output-dir' => $outputDir])
        ->assertSuccessful();

    $html = file_get_contents($outputDir.'/about.html');

    expect($html)
        ->toContain('<style>.about-hero { color: rebeccapurple; }</style>')
        ->toContain("<script>console.log('about page');</script>");
});
```

(Reuse whatever output-dir/cleanup helpers the existing tests in this file use;
if they pass `--content-path`, mirror that. The `about` page sidecars were added
in Task 2.)

- [ ] **Step 2: Run test**

Run: `./vendor/bin/pest tests/Feature/GenerateStaticCommandTest.php`
Expected: PASS (feature already works via shared views). If FAIL, verify the
`about.html` path matches how the command writes standalone pages.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/GenerateStaticCommandTest.php
git commit -m "test: assert sidecar css/js inline in static output"
```

---

## Task 6: Documentation page

**Files:**
- Create: `content/docs/<chapter>/<page>.md` (pick the next free numeric slot in an
  existing chapter, e.g. `content/docs/2-cloning-config/05-page-assets.md`, or add
  to the reference chapter `content/docs/5-reference/`)

- [ ] **Step 1: Pick the chapter and slot**

Run: `ls content/docs/` and `ls content/docs/5-reference/` to find the next free
numeric prefix. Use the reference chapter unless a more fitting one exists.

- [ ] **Step 2: Write the docs page**

Create the file (adjust path/number to the free slot) with:

```markdown
---
title: Page CSS & JS Assets
excerpt: Attach page-scoped styles and scripts by placing a same-named file next to your Markdown.
---

# Page CSS & JS Assets

Pergament can deliver page-scoped CSS and JavaScript without any configuration.
Place a file with the **same basename** as your Markdown file, using a `.css` or
`.js` extension, in the **same directory**. Its contents are embedded inline in
the rendered page — CSS into the document `<head>`, JS just before the closing
`</body>` tag.

## Naming convention

| Content type | Markdown file                                | Sidecar files                          |
|--------------|----------------------------------------------|----------------------------------------|
| Page         | `content/pages/home.md`                       | `content/pages/home.css`, `home.js`    |
| Blog post    | `content/blog/2024-01-15-hello/post.md`       | `post.css`, `post.js` (same directory) |
| Doc page     | `content/docs/0-getting-started/0-intro.md`   | `0-intro.css`, `0-intro.js`            |

Both files are optional and independent: a page may have only CSS, only JS, both,
or neither. You may attach at most one `.css` and one `.js` per Markdown file.

## How it is delivered

The file contents are injected **inline and verbatim** — CSS inside a `<style>`
tag, JS inside a `<script>` tag. There is no separate request and no caching layer;
the bytes ship with the page. This works the same way for live URLs and for the
static site generated by `pergament:generate-static`.

Because the content is injected raw, it is your responsibility to keep it valid:
avoid a literal `</style>` or `</script>` inside the file, as it would close the
surrounding tag prematurely.
```

- [ ] **Step 3: Verify it renders**

Run: `./vendor/bin/pest tests/Feature/DocumentationServiceTest.php`
Expected: PASS (existing tests still green; new page is content, not code).

- [ ] **Step 4: Commit**

```bash
git add content/docs/
git commit -m "docs: document sidecar css/js page assets"
```

---

## Task 7: Full suite + changelog

**Files:**
- Modify: `CHANGELOG.md` (if present, match existing entry style)

- [ ] **Step 1: Run the full test suite**

Run: `composer test`
Expected: lint clean, all tests pass, coverage ≥ 80%.

- [ ] **Step 2: Update changelog**

Run `ls CHANGELOG.md` first. If present, add an entry under the unreleased/top
section matching the existing format, e.g.:

```markdown
- Added: page-scoped CSS/JS sidecar assets — drop a same-named `.css`/`.js` next to a Markdown file to inject it inline (runtime + static).
```

If no `CHANGELOG.md` exists, skip this step.

- [ ] **Step 3: Commit**

```bash
git add CHANGELOG.md
git commit -m "docs: changelog entry for sidecar css/js assets"
```

---

## Self-Review Notes

- **Spec coverage:** helper (Task 1), all three content types service+view (Tasks 2-4), static (Task 5), raw-injection + naming docs (Task 6), tests throughout. All spec sections mapped.
- **Type consistency:** `SidecarAssets::forMarkdownFile(?string): array{styles: ?string, scripts: ?string}` used identically in all three services; views read `styles`/`scripts` keys consistently.
- **Slug/route assumptions** (page `about`/`home`, blog `hello-world`, docs `getting-started`/`introduction`, URL prefixes) are flagged in each task to confirm against existing tests/config before running — fixtures and existing tests already use these names.
