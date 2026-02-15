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
php artisan pergament:make-blog-post
```

**Create via artisan (non-interactive):**
```bash
php artisan pergament:make-blog-post my-post-slug \
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
php artisan pergament:make-doc-page
```

**Create via artisan (non-interactive):**
```bash
php artisan pergament:make-doc-page getting-started installation \
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
seo.title: "About - My Site"
---
```

**Layout options:**
- Default (omit `layout`): Standard centered content
- `layout: landing`: Full-width layout with block directive support

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

### Homepage Configuration

The homepage is configured in `config/pergament.php` under `homepage`:

- `type: page` + `source: home` — renders `content/pages/home.md`
- `type: blog-index` — shows the blog listing
- `type: doc-page` + `source: getting-started/introduction` — shows a doc page
- `type: redirect` + `source: /docs` — redirects to another URL

## Do and Don't

Do:
- Use kebab-case for all slugs
- Include `title` and `excerpt` in all front matter
- Use relative `.md` links for cross-references between content
- Place media files in the same directory as the content that references them
- Use numeric prefixes consistently for doc ordering (two digits: `01`, `02`, etc.)

Don't:
- Use absolute filesystem paths for images in Markdown — use relative paths
- Skip the date prefix on blog directories — it's required for date extraction
- Name blog post files anything other than `post.md`
- Use spaces or underscores in slugs — always use hyphens
