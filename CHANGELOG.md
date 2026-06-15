# Changelog

All notable changes to `laravel-pergament` will be documented in this file.

## v1.4.5 - 2026-06-15

### What's Changed

* build(deps): bump actions/checkout from 4.3.1 to 6.0.3 by @dependabot[bot] in https://github.com/rokde/laravel-pergament/pull/67
* build(deps): bump anthropics/claude-code-action from 1.0.133 to 1.0.140 by @dependabot[bot] in https://github.com/rokde/laravel-pergament/pull/68
* fix: generate .html urls in static sitemap and canonical tags by @rokde in https://github.com/rokde/laravel-pergament/pull/70

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.4.4...v1.4.5

## v1.4.4 - 2026-06-05

### What's Changed

* feat: page-scoped CSS/JS sidecar assets by @rokde in https://github.com/rokde/laravel-pergament/pull/66

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.4.3...v1.4.4

## Unreleased

- feat: page-scoped CSS/JS sidecar assets — drop a same-named `.css`/`.js` next to a Markdown file to inject it inline (runtime + static)

## v1.4.3 - 2026-06-05

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.4.2...v1.4.3

- replaced the Markdown Renderer

## v1.4.2 - 2026-06-05

### What's Changed

* feat(pages): support html frontmatter rendering by @rokde in https://github.com/rokde/laravel-pergament/pull/65

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.4.1...v1.4.2

## v1.4.1 - 2026-06-03

### What's Changed

* Page related functions by @rokde in https://github.com/rokde/laravel-pergament/pull/64

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.4.0...v1.4.1

## v1.4.0 - 2026-06-03

### What's Changed

* Add standalone Pergament static export CLI by @rokde in https://github.com/rokde/laravel-pergament/pull/63

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.3.0...v1.4.0

## v1.3.0 - 2026-06-01

### What's Changed

* Fix static HTML export portability by @rokde in https://github.com/rokde/laravel-pergament/pull/61
  security: pin GitHub Actions to commit SHAs by @rokde in https://github.com/rokde/laravel-pergament/pull/62

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.2.4...v1.3.0

## v1.2.4 - 2026-05-23

### What's Changed

* feat: add meta text-scale tag and CSS font-size support by @rokde in https://github.com/rokde/laravel-pergament/pull/60

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.2.3...v1.2.4

## v1.2.3 - 2026-05-23

### What's Changed

* build(deps): bump dependabot/fetch-metadata from 2.5.0 to 3.0.0 by @dependabot[bot] in https://github.com/rokde/laravel-pergament/pull/51
* fix: remove --parallel from test:ci to prevent Windows file rename race condition by @rokde in https://github.com/rokde/laravel-pergament/pull/53
* build(deps): bump dependabot/fetch-metadata from 3.0.0 to 3.1.0 by @dependabot[bot] in https://github.com/rokde/laravel-pergament/pull/54
* fix: decode HTML entities in TOC headings with ampersand by @rokde in https://github.com/rokde/laravel-pergament/pull/57

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.2.2...v1.2.3

## v1.2.2 - 2026-03-29

### What's Changed

* feat: add privacy-first server-side analytics by @rokde in https://github.com/rokde/laravel-pergament/pull/50

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.2.1...v1.2.2

## v1.2.1 - 2026-03-29

### What's Changed

* feat: support downloads within page content by @rokde in https://github.com/rokde/laravel-pergament/pull/49

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.2.0...v1.2.1

## v1.2.0 - 2026-03-26

### What's Changed

* Add Laravel 13 support by @rokde in https://github.com/rokde/laravel-pergament/pull/31

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.1.7...v1.2.0

## v1.1.7 - 2026-03-11

### What's Changed

* feat: serve raw markdown files for LLM requests by @rokde in https://github.com/rokde/laravel-pergament/pull/46

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.1.6...v1.1.7

## v1.1.6 - 2026-03-11

### What's Changed

* Add visual separator between TOC and content statistics by @rokde in https://github.com/rokde/laravel-pergament/pull/44
* feat: hide listen button on print by @rokde in https://github.com/rokde/laravel-pergament/pull/45

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.1.5...v1.1.6

## v1.1.5 - 2026-03-11

### What's Changed

* Add semantic <search> HTML element to search UI by @rokde in https://github.com/rokde/laravel-pergament/pull/37
* Add configurable content statistics component by @rokde in https://github.com/rokde/laravel-pergament/pull/38
* Add text-to-speech audio playback for content pages by @rokde in https://github.com/rokde/laravel-pergament/pull/39
* Improve tts by @rokde in https://github.com/rokde/laravel-pergament/pull/40

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.1.4...v1.1.5

## v1.1.4 - 2026-03-11

### What's Changed

* Support GitHub-style alert components by @rokde in https://github.com/rokde/laravel-pergament/pull/33
* feat: configure canonical url (#34) by @rokde in https://github.com/rokde/laravel-pergament/pull/35
* Add configurable footnote support by @rokde in https://github.com/rokde/laravel-pergament/pull/32
* improve code by @rokde in https://github.com/rokde/laravel-pergament/pull/36

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.1.3...v1.1.4

## v1.1.3 - 2026-02-27

### What's Changed

* Add claude GitHub actions 1772197192167 by @rokde in https://github.com/rokde/laravel-pergament/pull/20
* Fix copy buttons: unify to use SVG copy/check icons by @rokde in https://github.com/rokde/laravel-pergament/pull/21
* Claude/issue 22 20260227 1446 by @rokde in https://github.com/rokde/laravel-pergament/pull/26
* feat: extract header and footer as publishable Blade components by @rokde in https://github.com/rokde/laravel-pergament/pull/27
* feat: make prose tables horizontally scrollable by @rokde in https://github.com/rokde/laravel-pergament/pull/28
* feat: improve search with default entries and Cmd+K indicator by @rokde in https://github.com/rokde/laravel-pergament/pull/29

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.1.2...v1.1.3

## v1.1.2 - 2026-02-25

### What's Changed

* font sizes by @rokde in https://github.com/rokde/laravel-pergament/pull/17
* dyslexic font by @rokde in https://github.com/rokde/laravel-pergament/pull/19

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.1.1...v1.1.2

## v1.1.1 - 2026-02-21

### What's Changed

* Add Markdown support handling in PageController and routes by @rokde in https://github.com/rokde/laravel-pergament/pull/7

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.1.0...v1.1.1

## v1.1.0 - 2026-02-21

### What's Changed

All changes done by @rokde:

* Update page route to dynamically resolve slugs using PageService
* Mark `PageService` class as `readonly`
* Set default value for `pergament.content_path` in `PageService::basePath` method
* Add test to handle non-existent content path in PageService
* Update `test:unit` script to generate HTML coverage output
* Change `Carbon` to `CarbonImmutable` for `date` property in `BlogPost` class
* Set default for `pergament.content_path` and mark `BlogService` as `readonly`
* Mark `DocumentationService` class as `readonly`
* Set default value for `pergament.content_path` across services
* Add tests for handling empty or non-existent content paths in `DocumentationService`
* Add comprehensive tests for `FrontMatterParser` and `MarkdownRenderer` functionality
* Add comprehensive feature tests for controllers and utilities
* Add `MakePageCommand` with feature tests and register it in service provider
* Mark service classes as `readonly`
* Remove unnecessary newlines in XML generation across services for cleaner output
* Remove redundant newlines in `MakePageCommand` class
* Add `assertHeaderCaseInsensitive` macro and update tests to use it for flexible header value assertions

**Full Changelog**: https://github.com/rokde/laravel-pergament/compare/v1.0.0...v1.1.0

## First release - 2026-02-20

### What's Changed

* Potential fix for code scanning alert no. 1: Workflow does not contain permissions by @rokde in https://github.com/rokde/laravel-pergament/pull/2
* improve everything by @rokde in https://github.com/rokde/laravel-pergament/pull/4
* Bump actions/cache from 4 to 5 by @dependabot[bot] in https://github.com/rokde/laravel-pergament/pull/3
* improve commands by @rokde in https://github.com/rokde/laravel-pergament/pull/5

### New Contributors

* @rokde made their first contribution in https://github.com/rokde/laravel-pergament/pull/2
* @dependabot[bot] made their first contribution in https://github.com/rokde/laravel-pergament/pull/3

**Full Changelog**: https://github.com/rokde/laravel-pergament/commits/v1.0.0
