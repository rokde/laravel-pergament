<?php

declare(strict_types=1);

namespace Pergament\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\text;

final class MakeDocPageCommand extends Command
{
    protected $signature = 'pergament:make-doc-page
                            {chapter? : The chapter slug (e.g. getting-started)}
                            {page? : The page slug (e.g. installation)}
                            {--title= : The page title}
                            {--order= : The numeric sort order prefix (e.g. 01)}';

    protected $description = 'Create a new documentation page';

    public function handle(): int
    {
        $chapter = $this->argument('chapter') ?? text(
            label: 'What is the chapter slug?',
            placeholder: 'getting-started',
            required: true,
            hint: 'Use kebab-case, e.g. "getting-started"',
        );

        $page = $this->argument('page') ?? text(
            label: 'What is the page slug?',
            placeholder: 'installation',
            required: true,
            hint: 'Use kebab-case, e.g. "installation"',
        );

        $title = $this->option('title') ?? text(
            label: 'What is the page title?',
            default: Str::title(str_replace('-', ' ', $page)),
        );

        $order = $this->option('order') ?? text(
            label: 'What sort order prefix should this page have?',
            default: '01',
            hint: 'Numeric prefix like 01, 02, etc. Used for ordering pages in the chapter.',
        );

        $docsPath = config('pergament.content_path', base_path('content')).'/'.config('pergament.docs.path', 'docs');
        $chapterDir = $this->findOrCreateChapterDir($docsPath, $chapter, $order);
        $filename = mb_str_pad($order, 2, '0', STR_PAD_LEFT).'-'.$page.'.md';
        $filePath = $chapterDir.'/'.$filename;

        if (file_exists($filePath)) {
            $this->components->error("File already exists: {$filePath}");

            return self::FAILURE;
        }

        $content = <<<MARKDOWN
        ---
        title: {$title}
        excerpt: ""
        ---

        # {$title}

        Write your documentation here.
        MARKDOWN;

        if (! is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        file_put_contents($filePath, $content);

        $this->components->info("Documentation page created: {$filePath}");

        return self::SUCCESS;
    }

    private function findOrCreateChapterDir(string $docsPath, string $slug, string $order): string
    {
        if (is_dir($docsPath)) {
            foreach (scandir($docsPath) as $entry) {
                if (is_dir($docsPath.'/'.$entry) && preg_replace('/^\d+-/', '', $entry) === $slug) {
                    return $docsPath.'/'.$entry;
                }
            }
        }

        $dirName = mb_str_pad($order, 2, '0', STR_PAD_LEFT).'-'.$slug;
        $dirPath = $docsPath.'/'.$dirName;
        mkdir($dirPath, 0755, true);

        return $dirPath;
    }
}
