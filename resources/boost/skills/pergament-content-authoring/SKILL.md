---
name: pergament-content-authoring
description: Create and manage content for Laravel Pergament — blog posts, documentation pages, and standalone pages using Markdown with YAML front matter.
---

# Pergament Content Authoring

## When to Activate

- When asked to create, edit, or manage blog posts, documentation pages, or standalone pages
- When asked to write Markdown content with front matter for a Pergament-powered site
- When working with files under the `content/` directory (or the configured `pergament.content_path`)

## Content Types

### Blog Posts

Each blog post is a directory containing `post.md` and optional media files.

**Directory naming:** `{YYYY-MM-DD}-{slug}/`
**Location:** `content/blog/`

**Create via artisan (interactive prompts):**
```bash
php artisan pergament:make:post
```

**Create via artisan (non-interactive):**
```bash
php artisan pergament:make:post \
  --title="My Post Title" \
  --category="Tutorials" \
  --tags="laravel, php" \
  --author="Jane Doe" \
  --date="2025-03-15" \
  --excerpt="A brief summary of the post"
```

**Front matter fields:**
```yaml
---
title: "Post Title"
excerpt: "Brief summary shown on index pages"
category: "Category Name"
tags:
  - "tag-one"
  - "tag-two"
author: "Author Name"
# OR for multiple authors with details:
authors:
  - name: "Jane Doe"
    email: "jane@example.com"
    url: "https://janedoe.com"
    avatar: "https://example.com/avatar.jpg"
  - name: "John Smith"
# SEO overrides (optional):
seo.title: "Custom SEO Title"
seo.description: "Custom meta description"
seo.og_image: "https://example.com/image.png"
seo.robots: "noindex, nofollow"
---
```

**Media files:** Place images and other files alongside `post.md` in the same directory. Reference them with relative paths in Markdown:
```markdown
![Screenshot](screenshot.png)
```
These are automatically resolved to `/blog/media/{slug}/screenshot.png`.

### Documentation Pages

Documentation is organized into numbered chapters containing numbered pages.

**Directory structure:**
```
content/docs/
├── 0-getting-started/
│   ├── 01-introduction.md
│   └── 02-installation.md
└── 1-advanced/
    └── 01-customization.md
```

**Naming convention:** `{order}-{slug}` for both directories and files. The numeric prefix controls sort order but is stripped from URLs. URL becomes `/docs/getting-started/introduction`.

**Create via artisan (interactive):**
```bash
php artisan pergament:make:page
```

**Create via artisan (non-interactive):**
```bash
php artisan pergament:make:page --chapter=getting-started \
  --title="Installation Guide" \
  --order=02
```

**Front matter fields:**
```yaml
---
title: "Page Title"
excerpt: "Brief description"
seo.title: "Custom SEO Title"
---
```

**Cross-linking:** Use relative `.md` links between doc pages. They are automatically resolved:
```markdown
See [Installation](../getting-started/02-installation.md) for setup instructions.
```

**Dark mode image variants:** Place themed variants alongside the base image:
```
getting-started/
├── dashboard.png        # default/light image
├── dashboard.dark.png   # dark mode variant
└── dashboard.light.png  # explicit light variant (optional)
```
The renderer auto-generates themed `<img>` tags.

### Standalone Pages

Simple Markdown files for standalone pages like About, Contact, etc.

**Location:** `content/pages/{slug}.md`
**URL:** `/{slug}` (e.g., `content/pages/about.md` → `/about`)

**Front matter fields:**
```yaml
---
title: "About Us"
excerpt: "Learn about our team"
layout: landing
allow_html: true
seo.title: "About - My Site"
---
```

**Layout options:**
- Default (omit `layout`): Standard centered content
- `layout: landing`: Full-width layout with block directive support

**Custom HTML pages:**
- `allow_html: true`: Use for standalone pages that include custom HTML/CSS fragments. Pergament still renders the content through Markdown, but skips the default `prose` wrapper so the page can control its own layout and styling.

**Block directives for landing pages:**
```markdown
:::hero

# Welcome to Our Platform

[Get Started](/docs)

:::

:::features

## Why Choose Us

- Feature one
- Feature two

:::

:::cta

## Ready to Begin?

[Sign Up Now](/register)

:::
```

Block names (`hero`, `features`, `cta`, etc.) map to CSS classes `pergament-block-{name}` for styling.

**Download block directive:**

Wrap links in a `:::download` block to add the HTML `download` attribute, prompting the browser to save the file rather than navigate to it:

```markdown
:::download

[Download the report](report.pdf)
[Download CSV](data.csv)

:::
```

Works in blog posts, documentation pages, and standalone pages. Relative links are automatically rewritten to the correct media URL (see Downloads below). External links and anchors inside the block are not affected.

### Homepage Configuration

The homepage is configured in `config/pergament.php` under `homepage`:

- `type: page` + `source: home` — renders `content/pages/home.md`
- `type: blog-index` — shows the blog listing
- `type: doc-page` + `source: getting-started/introduction` — shows a doc page
- `type: redirect` + `source: /docs` — redirects to another URL

## Downloads

Any relative link in a blog post or documentation page that points to a file is automatically rewritten to the correct media URL — the same way images are resolved:

```markdown
[Download the guide](guide.pdf)
[Get the source archive](src.zip)
```

In a blog post this becomes `/blog/media/{slug}/guide.pdf`. In a documentation page it resolves to the equivalent docs media path. Place the files alongside `post.md` or the doc Markdown file.

External links (`http://`, `https://`), anchors (`#`), `mailto:` links, and links ending in `.md` are left unchanged.

To make the browser save the file to disk instead of navigating to it, wrap the link in a `:::download` block (see Block directives above).

## Page CSS & JS Assets

Attach page-scoped styles and scripts to any single piece of content — no config required. Place a file with the **same basename** as the Markdown file, using a `.css` or `.js` extension, in the **same directory**. Its contents are embedded inline in that page's rendered output — CSS into the document `<head>`, JavaScript just before the closing `</body>` tag.

| Content type | Markdown file | Sidecar files |
|--------------|---------------|---------------|
| Standalone page | `content/pages/about.md` | `content/pages/about.css`, `about.js` |
| Blog post | `content/blog/{YYYY-MM-DD}-{slug}/post.md` | `post.css`, `post.js` (same directory) |
| Documentation page | `content/docs/0-getting-started/01-introduction.md` | `01-introduction.css`, `01-introduction.js` |

Both files are optional and independent — a page may have only CSS, only JS, both, or neither. You may attach at most one `.css` and one `.js` per Markdown file; the basename must match exactly.

```
content/pages/
├── about.md     # the content
├── about.css    # injected into <head> for /about only
└── about.js     # injected before </body> for /about only
```

The contents are injected **inline and verbatim** — CSS inside a `<style>` tag, JavaScript inside a `<script>` tag. There is no separate request and no caching layer; the bytes ship with the page. This works the same way for live URLs and for the static site produced by `pergament:generate-static`.

Because the content is injected raw, keeping it valid is your responsibility: avoid a literal `</style>` or `</script>` inside the file, as it would close the surrounding tag prematurely.

## GitHub-Style Alerts

Use GitHub-style alert blocks to highlight important information:

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

Each alert renders as a styled `<div>` with an icon and color coding. Dark mode is handled automatically. Alerts can be disabled in the config (`markdown.alerts: false`), which falls back to a plain blockquote.

## Footnotes

Add inline footnotes with the `[^ref]` syntax (opt-in via config):

```markdown
Here is a sentence with a footnote.[^1]

[^1]: This is the footnote text. Supports **Markdown** formatting.
```

Footnote references render as superscript links that anchor to the footnote list at the bottom of the page. Enable in config: `markdown.footnotes: true`.

## Do and Don't

Do:
- Use kebab-case for all slugs
- Include `title` and `excerpt` in all front matter
- Use relative `.md` links for cross-references between content
- Place media files (images, PDFs, ZIPs, etc.) in the same directory as the content that references them
- Use numeric prefixes consistently for doc ordering (two digits: `01`, `02`, etc.)
- Use `:::download` blocks when you want files saved to disk rather than opened in the browser
- Attach page-scoped styles/scripts with a same-named `.css`/`.js` file next to the Markdown (see Page CSS & JS Assets)

Don't:
- Use absolute filesystem paths for images or files in Markdown — use relative paths
- Skip the date prefix on blog directories — it's required for date extraction
- Name blog post files anything other than `post.md`
- Use spaces or underscores in slugs — always use hyphens
