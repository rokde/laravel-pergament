# Page Actions Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an opt-in page actions toolbar for copying raw Markdown, opening the `.md` URL, and opening configured AI agent chat links.

**Architecture:** A global `pergament.page_actions` config block controls a reusable anonymous Blade component rendered on documentation pages, blog posts, and standard pages. A small JavaScript module handles copy-to-clipboard by fetching the component-provided Markdown URL.

**Tech Stack:** Laravel package config, Blade anonymous components, Pest feature tests, plain JavaScript IIFE modules.

---

### Task 1: Config Defaults

**Files:**
- Modify: `src/Config/pergament.php`
- Test: `tests/Feature/PageActionsTest.php`

- [ ] **Step 1: Write the failing config test**

```php
it('ships page actions disabled by default with copy open and claude configured', function (): void {
    expect(config('pergament.page_actions.enabled'))->toBeFalse();
    expect(config('pergament.page_actions.copy_markdown'))->toBeTrue();
    expect(config('pergament.page_actions.open_markdown'))->toBeTrue();
    expect(config('pergament.page_actions.ai_agents.claude.enabled'))->toBeTrue();
    expect(config('pergament.page_actions.ai_agents.chatgpt.enabled'))->toBeFalse();
    expect(config('pergament.page_actions.ai_agents.perplexity.enabled'))->toBeFalse();
    expect(config('pergament.page_actions.ai_agents.gemini.enabled'))->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Feature/PageActionsTest.php --filter="ships page actions disabled"`

Expected: FAIL because `pergament.page_actions.enabled` is missing.

- [ ] **Step 3: Add minimal config block**

Add a `page_actions` array to `src/Config/pergament.php` with `enabled => false`, `copy_markdown => true`, `open_markdown => true`, and configured agents for Claude, ChatGPT, Perplexity, and Gemini.

- [ ] **Step 4: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Feature/PageActionsTest.php --filter="ships page actions disabled"`

Expected: PASS.

### Task 2: Blade Rendering

**Files:**
- Create: `resources/views/components/page-actions.blade.php`
- Modify: `resources/views/docs/show.blade.php`
- Modify: `resources/views/blog/show.blade.php`
- Modify: `resources/views/pages/show.blade.php`
- Test: `tests/Feature/PageActionsTest.php`

- [ ] **Step 1: Write failing rendering tests**

Assert the toolbar is absent by default, appears on docs/blog/pages when `pergament.page_actions.enabled` is true, renders `.md` links, and encodes configured agent URLs with the current page URL.

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/pest tests/Feature/PageActionsTest.php`

Expected: FAIL because the component and markup do not exist yet.

- [ ] **Step 3: Create component and render it in content views**

Create a print-hidden toolbar with a copy button, open Markdown link, and enabled agent links. Use `request()->url()` as the canonical page URL and `request()->fullUrlWithoutQuery([]).'.md'` equivalent logic via `rtrim(request()->url(), '/').'.md'` for Markdown URLs.

- [ ] **Step 4: Run tests to verify they pass**

Run: `./vendor/bin/pest tests/Feature/PageActionsTest.php`

Expected: PASS.

### Task 3: Copy Markdown JavaScript

**Files:**
- Create: `resources/js/page-actions.js`
- Modify: `resources/js/pergament.js`

- [ ] **Step 1: Add JS module**

Create a guarded IIFE that finds `[data-pergament-copy-page]`, fetches `data-markdown-url`, writes the response text to `navigator.clipboard`, and temporarily changes button state to copied.

- [ ] **Step 2: Import module**

Add `import './page-actions.js';` to `resources/js/pergament.js`.

- [ ] **Step 3: Verify package tests**

Run: `composer test:unit`

Expected: PASS.

### Task 4: Full Verification

**Files:**
- All changed files

- [ ] **Step 1: Run lint**

Run: `composer test:lint`

Expected: PASS.

- [ ] **Step 2: Run full test suite**

Run: `composer test`

Expected: PASS.
