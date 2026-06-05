---
title: Page CSS & JS Assets
excerpt: Attach page-scoped styles and scripts by placing a same-named file next to your Markdown.
---

# Page CSS & JS Assets

Pergament can deliver page-scoped CSS and JavaScript without any configuration.
Place a file with the **same basename** as your Markdown file, using a `.css` or
`.js` extension, in the **same directory**. Its contents are embedded inline in
the rendered page — CSS into the document `<head>`, JavaScript just before the
closing `</body>` tag.

## Naming convention

| Content type | Markdown file                              | Sidecar files                          |
|--------------|--------------------------------------------|----------------------------------------|
| Page         | `content/pages/home.md`                     | `content/pages/home.css`, `home.js`    |
| Blog post    | `content/blog/2024-01-15-hello/post.md`     | `post.css`, `post.js` (same directory) |
| Doc page     | `content/docs/0-getting-started/0-intro.md` | `0-intro.css`, `0-intro.js`            |

Both files are optional and independent: a page may have only CSS, only JS, both,
or neither. You may attach at most one `.css` and one `.js` per Markdown file.

## How it is delivered

The file contents are injected **inline and verbatim** — CSS inside a `<style>`
tag, JavaScript inside a `<script>` tag. There is no separate request and no
caching layer; the bytes ship with the page. This works the same way for live
URLs and for the static site produced by `pergament:generate-static`.

Because the content is injected raw, keeping it valid is your responsibility:
avoid a literal `</style>` or `</script>` inside the file, since it would close
the surrounding tag prematurely.
