<?php

declare(strict_types=1);

it('ships page actions disabled by default with copy open and claude configured', function (): void {
    expect(config('pergament.page_actions.enabled'))->toBeFalse();
    expect(config('pergament.page_actions.copy_markdown'))->toBeTrue();
    expect(config('pergament.page_actions.open_markdown'))->toBeTrue();
    expect(config('pergament.page_actions.ai_agents.claude.enabled'))->toBeTrue();
    expect(config('pergament.page_actions.ai_agents.chatgpt.enabled'))->toBeTrue();
    expect(config('pergament.page_actions.ai_agents.perplexity.enabled'))->toBeTrue();
    expect(config('pergament.page_actions.ai_agents.gemini.enabled'))->toBeTrue();
});

it('does not render page actions by default', function (): void {
    $this->get('/about')
        ->assertStatus(200)
        ->assertDontSee('data-pergament-page-actions', false);
});

it('renders page actions for documentation pages when enabled', function (): void {
    config()->set('pergament.page_actions.enabled', true);

    $this->get('/docs/getting-started/introduction')
        ->assertStatus(200)
        ->assertSee('data-pergament-page-actions', false)
        ->assertSee('data-markdown-url="http://localhost/docs/getting-started/introduction.md"', false)
        ->assertSee('href="http://localhost/docs/getting-started/introduction.md"', false)
        ->assertSee('https://claude.ai/new?q=I%E2%80%99d+like+to+discuss+the+content+from+http%3A%2F%2Flocalhost%2Fdocs%2Fgetting-started%2Fintroduction', false);
});

it('renders page actions for blog posts when enabled', function (): void {
    config()->set('pergament.page_actions.enabled', true);

    $this->get('/blog/hello-world')
        ->assertStatus(200)
        ->assertSee('data-pergament-page-actions', false)
        ->assertSee('data-markdown-url="http://localhost/blog/hello-world.md"', false)
        ->assertSee('href="http://localhost/blog/hello-world.md"', false);
});

it('renders page actions for standalone pages when enabled', function (): void {
    config()->set('pergament.page_actions.enabled', true);

    $this->get('/about')
        ->assertStatus(200)
        ->assertSee('data-pergament-page-actions', false)
        ->assertSee('data-markdown-url="http://localhost/about.md"', false)
        ->assertSee('href="http://localhost/about.md"', false);
});

it('only renders enabled agent links', function (): void {
    config()->set('pergament.page_actions.enabled', true);
    config()->set('pergament.page_actions.ai_agents.chatgpt.enabled', true);

    $this->get('/about')
        ->assertStatus(200)
        ->assertSee('ChatGPT', false)
        ->assertSee('Perplexity', false)
        ->assertSee('Gemini', false);
});

it('renders multiple enabled agents as a native html split dropdown', function (): void {
    config()->set('pergament.page_actions.enabled', true);
    config()->set('pergament.page_actions.ai_agents.chatgpt.enabled', true);

    $this->get('/about')
        ->assertStatus(200)
        ->assertSee('data-pergament-agent-split-button', false)
        ->assertSee('popovertarget="pergament-ai-agents-menu"', false)
        ->assertSee('id="pergament-ai-agents-menu"', false)
        ->assertSee('popover', false)
        ->assertSee('Chat in Claude', false)
        ->assertSee('ChatGPT', false);
});
