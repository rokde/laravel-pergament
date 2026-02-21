<?php

declare(strict_types=1);

namespace Pergament\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;
use function Laravel\Prompts\textarea;

final class MakePageCommand extends Command
{
    protected $signature = 'pergament:make:page
                            {--title= : Page title — slug is auto-derived; skips the title prompt}
                            {--excerpt= : Page excerpt — skips the excerpt prompt}
                            {--layout= : Layout name (e.g. landing) — skips the layout prompt}';

    protected $description = 'Create a new standalone page';

    public function handle(): int
    {
        $pagesPath = config('pergament.content_path', base_path('content'))
            .'/'.config('pergament.pages.path', 'pages');

        if (! is_dir($pagesPath)) {
            mkdir($pagesPath, 0755, true);
        }

        $title = $this->option('title') ?? text(
            label: 'Page title',
            required: true,
            hint: 'The heading shown in navigation. The URL slug is derived automatically.',
        );

        $slug = Str::slug($title);
        $filePath = $pagesPath.'/'.$slug.'.md';

        if (file_exists($filePath)) {
            $this->components->error("Page already exists: {$filePath}");

            return self::FAILURE;
        }

        $interactive = $this->option('title') === null;
        $excerpt = $this->option('excerpt') ?? ($interactive ? textarea(
            label: 'Excerpt',
            placeholder: 'Brief description shown in search results…',
            hint: 'Optional — leave empty to skip.',
            rows: 3,
        ) : '');

        $layout = $this->resolveLayout();

        file_put_contents($filePath, $this->buildContent($title, (string) $excerpt, $layout));

        $this->components->info("Page created: {$filePath}");

        return self::SUCCESS;
    }

    private function resolveLayout(): string
    {
        if ($this->option('layout') !== null) {
            return (string) $this->option('layout');
        }

        return select(
            label: 'Layout',
            options: [
                '' => 'Default',
                'landing' => 'Landing',
            ],
            default: '',
            hint: 'Choose a layout for this page.',
        );
    }

    private function buildContent(string $title, string $excerpt, string $layout): string
    {
        $escapedExcerpt = '"'.str_replace('"', '\\"', $excerpt).'"';

        $lines = [
            '---',
            "title: {$title}",
            "excerpt: {$escapedExcerpt}",
        ];

        if ($layout !== '') {
            $lines[] = "layout: {$layout}";
        }

        return implode("\n", array_merge($lines, [
            '---',
            '',
            "# {$title}",
            '',
            'Write your page content here.',
            '',
        ]));
    }
}
