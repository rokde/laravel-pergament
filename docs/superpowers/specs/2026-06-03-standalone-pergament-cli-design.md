# Standalone Pergament CLI Design

## Goal

Build a standalone `pergament` CLI entry point that can generate a static Pergament site without requiring an existing Laravel application. The first release supports only static export and is intended to work both from this repository and as a Composer-installed binary in downstream projects.

Primary command:

```bash
vendor/bin/pergament generate-static <output-dir> [--content-path=...] [--prefix=...] [--base-url=...] [--clean]
```

Repository-local usage:

```bash
./bin/pergament generate-static <output-dir> [--content-path=...] [--prefix=...] [--base-url=...] [--clean]
```

The existing Artisan command name is accepted as an alias:

```bash
vendor/bin/pergament pergament:generate-static <output-dir>
```

## Non-Goals

- Do not replace the existing `pergament:generate-static` Artisan command.
- Do not implement a framework-independent renderer.
- Do not add `make:doc`, `make:page`, or `make:blog-post` to the standalone CLI in the first release.
- Do not duplicate static export logic outside the existing command.

## Recommended Approach

Use a thin `bin/pergament` script that boots a minimal Illuminate/Laravel-style console environment and reuses `Pergament\Console\Commands\GenerateStaticCommand`.

This keeps the export behavior identical between Laravel Artisan and standalone CLI while avoiding a second rendering path. Pergament still uses Blade views, config, service container resolution, and Laravel helper functions internally; the standalone CLI simply provides the minimal runtime these features need.

## Architecture

### `bin/pergament`

The binary is responsible for:

- Locating `vendor/autoload.php` for both repository and Composer-installed contexts.
- Printing a clear error when dependencies are missing.
- Normalizing `generate-static` to the existing `pergament:generate-static` command name before dispatch.
- Delegating boot and execution to a small standalone console bootstrap class.

The binary is registered in `composer.json` via the `bin` key so downstream projects receive `vendor/bin/pergament` automatically.

### Standalone Console Bootstrap

Add a focused bootstrap class, for example `Pergament\Console\StandaloneApplication`.

Responsibilities:

- Create an Illuminate container/application suitable for console execution.
- Register config, filesystem, events, view, Blade compiler, routing/URL support if needed by views, and console services.
- Load Pergament default config from `src/Config/pergament.php`.
- Register Pergament views under the `pergament::` namespace.
- Register anonymous Blade components from `resources/views/components` with the `pergament` prefix.
- Share asset version variables expected by layouts.
- Register only `GenerateStaticCommand` for the first standalone release.

The existing `PergamentServiceProvider` should remain the Laravel integration point. The standalone bootstrap may reuse it where practical, but it can also perform a small amount of explicit setup if full provider bootstrapping requires too much application surface area.

### Existing Static Export Command

`Pergament\Console\Commands\GenerateStaticCommand` remains the single source of export behavior.

The command already:

- Reads content through `DocumentationService`, `BlogService`, and `PageService`.
- Renders Blade views through `view('pergament::...')->render()`.
- Writes HTML, Markdown sidecars, media, feeds, sitemap, robots, llms, and search index files.
- Supports `--content-path`, `--prefix`, `--base-url`, and `--clean`.

Any standalone-specific work should be limited to making its dependencies available, not changing export semantics.

## Composer Dependencies

The current package requires `illuminate/support`, but standalone CLI execution needs additional Illuminate components. Add the smallest set needed to support console execution and Blade rendering, expected to include:

- `illuminate/console`
- `illuminate/container`
- `illuminate/config`
- `illuminate/view`
- `illuminate/filesystem`
- `illuminate/events`
- `illuminate/routing` if route generation is required by the shipped views

Versions should follow the existing Laravel compatibility range: `^11.0|^12.0|^13.0`.

## GitHub Actions And GitHub Pages

The standalone CLI must be suitable for CI usage, especially GitHub Actions publishing to GitHub Pages.

Expected workflow shape:

```yaml
name: Deploy Pergament site

on:
  push:
    branches: [main]

permissions:
  contents: read
  pages: write
  id-token: write

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
      - run: composer install --no-dev --prefer-dist --no-interaction
      - run: vendor/bin/pergament generate-static public --content-path=content --base-url="https://example.github.io/repository"
      - uses: actions/upload-pages-artifact@v3
        with:
          path: public

  deploy:
    needs: build
    runs-on: ubuntu-latest
    environment:
      name: github-pages
      url: ${{ steps.deployment.outputs.page_url }}
    steps:
      - id: deployment
        uses: actions/deploy-pages@v4
```

For project pages hosted below a repository path, users should pass:

- `--base-url=https://<owner>.github.io/<repo>` for canonical URLs, feeds, and sitemap.
- `--prefix=<repo>` only if the generated site intentionally needs Pergament routes below that path. If `base-url` alone is sufficient for generated absolute URLs, avoid forcing a prefix.

The CLI should return a non-zero exit code for bootstrap failures and invalid CLI usage. The existing export command currently returns success even when content-level link/render errors are collected as warnings; that behavior should stay unchanged unless separately redesigned.

## Data Flow

1. User runs `vendor/bin/pergament generate-static public --content-path=content --base-url=...`.
2. The binary loads Composer autoload and starts the standalone bootstrap.
3. The bootstrap creates the minimal Illuminate console environment and registers Pergament services.
4. The CLI dispatches to `GenerateStaticCommand`.
5. The command temporarily applies CLI config overrides.
6. Services parse Markdown/front matter and render content to DTOs/arrays.
7. Blade views render HTML using the same templates as Laravel runtime.
8. Static files are written to the output directory.

## Error Handling

- Missing `vendor/autoload.php`: print a concise install/dependency error and exit non-zero.
- Unknown command: print usage and exit non-zero.
- Bootstrap failure: print the exception message and exit non-zero.
- Export warnings collected by `GenerateStaticCommand`: preserve current behavior and display warnings while returning success.
- Filesystem write failures: let the command report or throw as it does today; standalone bootstrap should not swallow exceptions.

## Testing

Keep the existing `GenerateStaticCommandTest` coverage for Artisan behavior.

Add standalone CLI coverage that executes `bin/pergament generate-static` against fixture content and verifies representative output files:

- `index.html`
- documentation HTML file
- blog HTML file
- `search.json`
- `sitemap.xml`

Also test invalid usage or unknown command handling if practical without making tests brittle.

The test should verify that the standalone path works without relying on a host Laravel application, while still running inside the package test environment.

## Open Implementation Notes

- Prefer the smallest bootstrap surface that supports the existing views.
- If route generation in Blade views is difficult in standalone mode, add a minimal route set matching Pergament route names rather than changing views.
- Keep asset paths compatible with static hosting and GitHub Pages.
- Do not introduce backward compatibility layers beyond the supported command alias.
