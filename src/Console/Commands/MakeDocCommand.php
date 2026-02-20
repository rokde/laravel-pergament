<?php

declare(strict_types=1);

namespace Pergament\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;

final class MakeDocCommand extends Command
{
    protected $signature = 'pergament:make:doc
                            {--chapter= : Chapter slug — skips the chapter select prompt}
                            {--chapter-title= : Title for a brand-new chapter (used together with --chapter)}
                            {--title= : Page title — slug is auto-derived; skips the title prompt}
                            {--excerpt= : Page excerpt — skips the excerpt prompt}
                            {--order= : Numeric sort-order prefix, e.g. 01 — skips the position prompt}';

    protected $description = 'Create a new documentation page';

    public function handle(): int
    {
        $docsPath = config('pergament.content_path', base_path('content'))
            .'/'.config('pergament.docs.path', 'docs');

        [$chapterDir, $defaultPrefix] = $this->resolveChapter($docsPath);

        // --- Title (slug auto-derived) ---
        $title = $this->option('title') ?? text(
            label: 'Page title',
            required: true,
            hint: 'The heading shown in navigation. The URL slug is derived automatically.',
        );

        $slug = Str::slug($title);

        // --- Excerpt (only prompt when running interactively, i.e. --title was not pre-supplied) ---
        $interactive = $this->option('title') === null;
        $excerpt = $this->option('excerpt') ?? ($interactive ? textarea(
            label: 'Excerpt',
            placeholder: 'Brief description shown in navigation and search results…',
            hint: 'Optional — leave empty to skip.',
            rows: 3,
        ) : '');

        // --- Position / order prefix ---
        $prefix = $this->option('order')
            ?? $defaultPrefix
            ?? $this->resolvePagePrefix($chapterDir);

        $prefix = str_pad((string) $prefix, 2, '0', STR_PAD_LEFT);
        $filePath = $chapterDir.'/'.$prefix.'-'.$slug.'.md';

        if (file_exists($filePath)) {
            $this->components->error("File already exists: {$filePath}");

            return self::FAILURE;
        }

        file_put_contents($filePath, $this->buildContent($title, (string) $excerpt));

        $this->components->info("Documentation page created: {$filePath}");

        return self::SUCCESS;
    }

    /**
     * Resolve or create the chapter directory.
     *
     * Returns [chapterDir, pagePrefix|null]:
     *   - pagePrefix is '01' when a brand-new chapter was just created (no position prompt needed)
     *   - pagePrefix is null when the caller should ask the user for a position
     *
     * @return array{string, string|null}
     */
    private function resolveChapter(string $docsPath): array
    {
        if ($chapterSlug = $this->option('chapter')) {
            return $this->resolveChapterBySlug($docsPath, $chapterSlug);
        }

        return $this->promptForChapter($docsPath);
    }

    /**
     * @return array{string, string|null}
     */
    private function resolveChapterBySlug(string $docsPath, string $slug): array
    {
        $existing = $this->findChapterDirBySlug($docsPath, $slug);

        if ($existing !== null) {
            return [$existing, null];
        }

        // Create a new chapter directory
        $chapters = $this->scanChapters($docsPath);
        $prefix = $this->nextChapterPrefix($chapters);
        $dir = $docsPath.'/'.$prefix.'-'.$slug;
        mkdir($dir, 0755, true);

        return [$dir, '01'];
    }

    /**
     * @return array{string, string|null}
     */
    private function promptForChapter(string $docsPath): array
    {
        $chapters = $this->scanChapters($docsPath);

        $options = ['__new__' => '✚  Create a new chapter'];
        foreach ($chapters as $ch) {
            $options[$ch['dirName']] = $ch['prefix'].' · '.$ch['title'];
        }

        $default = count($chapters) > 0
            ? array_key_first(array_slice($options, 1, null, true))
            : '__new__';

        $selected = select(
            label: 'Chapter',
            options: $options,
            default: $default,
            scroll: 10,
            hint: 'Choose an existing chapter or create a new one.',
        );

        if ($selected !== '__new__') {
            return [$docsPath.'/'.$selected, null];
        }

        // Create new chapter
        $chapterTitle = $this->option('chapter-title') ?? text(
            label: 'New chapter title',
            required: true,
            hint: 'Will be converted to a URL slug automatically.',
        );

        $chapterSlug = Str::slug($chapterTitle);
        $prefix = $this->nextChapterPrefix($chapters);
        $dir = $docsPath.'/'.$prefix.'-'.$chapterSlug;
        mkdir($dir, 0755, true);

        return [$dir, '01'];
    }

    private function resolvePagePrefix(string $chapterDir): string
    {
        $pages = $this->scanPages($chapterDir);

        if (empty($pages)) {
            return '01';
        }

        $options = ['first' => '↑  As first page'];
        foreach ($pages as $i => $page) {
            $options['after:'.$i] = 'After "'.$page['title'].'"';
        }
        $options['last'] = '↓  As last page';

        $position = select(
            label: 'Position',
            options: $options,
            default: 'last',
            scroll: 15,
            hint: 'Where should the new page appear in this chapter?',
        );

        if ($position === 'first') {
            return '00';
        }

        if ($position === 'last') {
            return str_pad((string) ((int) end($pages)['prefix'] + 1), 2, '0', STR_PAD_LEFT);
        }

        $idx = (int) explode(':', $position, 2)[1];

        return str_pad((string) ((int) $pages[$idx]['prefix'] + 1), 2, '0', STR_PAD_LEFT);
    }

    private function buildContent(string $title, string $excerpt): string
    {
        $escapedExcerpt = '"'.str_replace('"', '\\"', $excerpt).'"';

        return implode("\n", [
            '---',
            "title: {$title}",
            "excerpt: {$escapedExcerpt}",
            '---',
            '',
            "# {$title}",
            '',
            'Write your documentation here.',
            '',
        ]);
    }

    /**
     * @return list<array{dirName: string, prefix: string, slug: string, title: string}>
     */
    private function scanChapters(string $docsPath): array
    {
        if (! is_dir($docsPath)) {
            return [];
        }

        $chapters = [];

        foreach (scandir($docsPath) as $entry) {
            if (! is_dir($docsPath.'/'.$entry)) {
                continue;
            }

            if (! preg_match('/^(\d+)-(.+)$/', $entry, $m)) {
                continue;
            }

            $slug = $m[2];
            $chapters[] = [
                'dirName' => $entry,
                'prefix' => $m[1],
                'slug' => $slug,
                'title' => Str::title(str_replace('-', ' ', $slug)),
            ];
        }

        usort($chapters, fn (array $a, array $b): int => $a['prefix'] <=> $b['prefix']);

        return $chapters;
    }

    /**
     * @return list<array{filename: string, prefix: string, slug: string, title: string}>
     */
    private function scanPages(string $chapterDir): array
    {
        if (! is_dir($chapterDir)) {
            return [];
        }

        $pages = [];

        foreach (scandir($chapterDir) as $file) {
            if (! str_ends_with($file, '.md')) {
                continue;
            }

            if (! preg_match('/^(\d+)-(.+)\.md$/', $file, $m)) {
                continue;
            }

            $slug = $m[2];
            $pages[] = [
                'filename' => $file,
                'prefix' => $m[1],
                'slug' => $slug,
                'title' => $this->readPageTitle($chapterDir.'/'.$file, $slug),
            ];
        }

        usort($pages, fn (array $a, array $b): int => $a['prefix'] <=> $b['prefix']);

        return $pages;
    }

    private function readPageTitle(string $filePath, string $fallbackSlug): string
    {
        $content = file_get_contents($filePath);

        if (preg_match('/^---\s*\n.*?^title:\s*["\']?(.+?)["\']?\s*$/ms', $content, $m)) {
            return trim($m[1]);
        }

        return Str::title(str_replace('-', ' ', $fallbackSlug));
    }

    private function findChapterDirBySlug(string $docsPath, string $slug): ?string
    {
        if (! is_dir($docsPath)) {
            return null;
        }

        foreach (scandir($docsPath) as $entry) {
            if (! is_dir($docsPath.'/'.$entry)) {
                continue;
            }

            if (preg_replace('/^\d+-/', '', $entry) === $slug) {
                return $docsPath.'/'.$entry;
            }
        }

        return null;
    }

    /**
     * @param  list<array{prefix: string, ...}>  $chapters
     */
    private function nextChapterPrefix(array $chapters): string
    {
        if (empty($chapters)) {
            return '01';
        }

        $max = max(array_column($chapters, 'prefix'));

        return str_pad((string) ((int) $max + 1), 2, '0', STR_PAD_LEFT);
    }
}
