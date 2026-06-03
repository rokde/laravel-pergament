@php
    $config = config('pergament.page_actions', []);
    $pageUrl = request()->url();
    $markdownUrl = rtrim($pageUrl, '/').'.md';
    $agents = collect($config['ai_agents'] ?? [])->filter(fn (array $agent): bool => (bool) ($agent['enabled'] ?? false));
    $primaryAgent = $agents->first();
    $secondaryAgents = $agents->slice(1);
    $agentUrl = fn (array $agent): string => str_replace('{url}', urlencode($pageUrl), $agent['url'] ?? '');
    $hasActions = ($config['copy_markdown'] ?? false) || ($config['open_markdown'] ?? false) || $agents->isNotEmpty();
@endphp

@if(($config['enabled'] ?? false) && $hasActions)
    <div data-pergament-page-actions class="flex flex-wrap items-center gap-2 print:hidden" role="toolbar" aria-label="Page actions">
        @if($config['copy_markdown'] ?? false)
            <button
                type="button"
                data-pergament-copy-page
                data-markdown-url="{{ $markdownUrl }}"
                aria-label="Copy markdown"
                title="Copy markdown"
                class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 dark:border-gray-700 px-2.5 py-1.5 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer"
            >
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75A1.125 1.125 0 0 1 3.75 20.625V7.875c0-.621.504-1.125 1.125-1.125H6.75" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 15.75h10.125c.621 0 1.125-.504 1.125-1.125v-9.75c0-.621-.504-1.125-1.125-1.125H9.75c-.621 0-1.125.504-1.125 1.125v9.75c0 .621.504 1.125 1.125 1.125Z" />
                </svg>
                <span data-copy-label>Copy Page</span>
            </button>
        @endif

        @if($config['open_markdown'] ?? false)
            <a
                href="{{ $markdownUrl }}"
                target="_blank"
                rel="noopener"
                aria-label="Open markdown"
                title="Open markdown"
                class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 dark:border-gray-700 px-2.5 py-1.5 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            >
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                <span>Open Markdown</span>
            </a>
        @endif

        @if($agents->count() === 1)
            <a
                href="{{ $agentUrl($primaryAgent) }}"
                target="_blank"
                rel="noopener"
                aria-label="Chat in {{ $primaryAgent['label'] ?? 'AI agent' }}"
                title="Chat in {{ $primaryAgent['label'] ?? 'AI agent' }}"
                class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 dark:border-gray-700 px-2.5 py-1.5 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            >
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                </svg>
                <span>Chat in {{ $primaryAgent['label'] ?? 'AI agent' }}</span>
            </a>
        @elseif($agents->count() > 1)
            <div data-pergament-agent-split-button class="relative inline-flex items-stretch">
                <a
                    href="{{ $agentUrl($primaryAgent) }}"
                    target="_blank"
                    rel="noopener"
                    aria-label="Chat in {{ $primaryAgent['label'] ?? 'AI agent' }}"
                    title="Chat in {{ $primaryAgent['label'] ?? 'AI agent' }}"
                    class="inline-flex items-center gap-1.5 rounded-l-md border border-gray-200 dark:border-gray-700 px-2.5 py-1.5 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                    </svg>
                    <span>Chat in {{ $primaryAgent['label'] ?? 'AI agent' }}</span>
                </a>

                <button
                    type="button"
                    popovertarget="pergament-ai-agents-menu"
                    popovertargetaction="toggle"
                    aria-label="Choose AI agent"
                    title="Choose AI agent"
                    style="anchor-name: --pergament-ai-agents-anchor"
                    class="inline-flex items-center rounded-r-md border border-l-0 border-gray-200 dark:border-gray-700 px-2 py-1.5 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <div
                    id="pergament-ai-agents-menu"
                    popover
                    style="position-anchor: --pergament-ai-agents-anchor; position-area: bottom span-left; margin: 0.25rem 0 0; inset: auto"
                    class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-1 shadow-lg backdrop:bg-transparent"
                >
                    <div class="flex min-w-44 flex-col" role="menu" aria-label="AI agents">
                        @foreach($secondaryAgents as $agent)
                            <a
                                href="{{ $agentUrl($agent) }}"
                                target="_blank"
                                rel="noopener"
                                role="menuitem"
                                class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white transition-colors"
                            >
                                Chat in {{ $agent['label'] ?? 'AI agent' }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif
