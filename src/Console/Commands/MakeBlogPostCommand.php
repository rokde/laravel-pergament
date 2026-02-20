<?php

declare(strict_types=1);

namespace Pergament\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Pergament\Services\BlogService;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;

final class MakeBlogPostCommand extends Command
{
    protected $signature = 'pergament:make:post
                            {--title= : The post title}
                            {--category= : The post category}
                            {--tags= : Comma-separated list of tags}
                            {--author= : The author name}
                            {--date= : The publish date (YYYY-MM-DD, defaults to today)}
                            {--excerpt= : A short excerpt for the post}';

    protected $description = 'Create a new blog post';

    public function __construct(private BlogService $blogService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $title = $this->option('title') ?? text(
            label: 'What is the post title?',
            default: Str::title(str_replace('-', ' ', 'hello-world')),
        );

        $slug = Str::slug($title);

        $date = $this->option('date') ?? text(
            label: 'What is the publish date?',
            default: Carbon::today()->format('Y-m-d'),
            hint: 'Format: YYYY-MM-DD',
        );

        $category = $this->resolveCategory();
        $tags = $this->resolveTags();

        $author = $this->option('author') ?? text(
            label: 'Who is the author?',
            hint: 'Leave empty to use default authors from config',
        );

        $excerpt = $this->option('excerpt') ?? textarea(
            label: 'Write a short excerpt for this post',
            hint: 'A brief summary shown on index pages',
        );

        $blogPath = config('pergament.content_path', base_path('content')).'/'.config('pergament.blog.path', 'blog');
        $dirName = $date.'-'.$slug;
        $dirPath = $blogPath.'/'.$dirName;

        if (is_dir($dirPath)) {
            $this->components->error("Post directory already exists: {$dirPath}");

            return self::FAILURE;
        }

        mkdir($dirPath, 0755, true);

        $frontMatter = $this->buildFrontMatter($title, $excerpt, $category, $tags, $author);
        $content = "---\n".$frontMatter."---\n\n# {$title}\n\nWrite your post content here.\n";

        file_put_contents($dirPath.'/post.md', $content);

        $this->components->info("Blog post created: {$dirPath}/post.md");

        return self::SUCCESS;
    }

    private function resolveCategory(): string
    {
        if ($this->option('category') !== null) {
            return (string) $this->option('category');
        }

        $categories = $this->blogService->getCategories();

        if ($categories->isEmpty()) {
            return text(
                label: 'What category does this post belong to?',
                hint: 'Leave empty for no category',
            );
        }

        $options = ['__none__' => 'No category']
            + $categories->mapWithKeys(fn (string $c): array => [$c => $c])->toArray()
            + ['__new__' => 'New category...'];

        $selected = select(
            label: 'What category does this post belong to?',
            options: $options,
            hint: 'Select an existing category or create a new one',
        );

        return match ($selected) {
            '__none__' => '',
            '__new__' => text(
                label: 'Enter the new category name',
                hint: 'Leave empty for no category',
            ),
            default => (string) $selected,
        };
    }

    /**
     * @return array<int, string>
     */
    private function resolveTags(): array
    {
        if ($this->option('tags') !== null) {
            $tagsOption = (string) $this->option('tags');

            if ($tagsOption === '') {
                return [];
            }

            return array_map('trim', explode(',', $tagsOption));
        }

        $existingTags = $this->blogService->getTags();

        if ($existingTags->isEmpty()) {
            return $this->collectNewTags();
        }

        $options = $existingTags->mapWithKeys(fn (string $t): array => [$t => $t])->toArray();
        $options['__add_new__'] = 'Add new tags...';

        $selected = multiselect(
            label: 'Which tags should this post have?',
            options: $options,
            hint: 'Space to select, enter to confirm. Select "Add new tags..." to add more.',
        );

        $selectedTags = array_values(array_filter($selected, fn (string $tag): bool => $tag !== '__add_new__'));

        if (in_array('__add_new__', $selected, true)) {
            $newTags = $this->collectNewTags();
            $selectedTags = array_merge($selectedTags, $newTags);
        }

        return $selectedTags;
    }

    /**
     * @return array<int, string>
     */
    private function collectNewTags(): array
    {
        $tags = [];

        while (true) {
            $tag = text(
                label: 'Add a tag',
                hint: 'Leave empty to stop adding tags',
            );

            if ($tag === '') {
                break;
            }

            $tags[] = $tag;
        }

        return $tags;
    }

    private function buildFrontMatter(
        string $title,
        string $excerpt,
        string $category,
        array $tags,
        string $author,
    ): string {
        $lines = [];
        $lines[] = 'title: "'.addcslashes($title, '"').'"';
        $lines[] = 'excerpt: "'.addcslashes($excerpt, '"').'"';

        if ($category !== '') {
            $lines[] = 'category: "'.addcslashes($category, '"').'"';
        }

        if ($tags !== []) {
            $lines[] = 'tags:';
            foreach ($tags as $tag) {
                $lines[] = '  - "'.addcslashes($tag, '"').'"';
            }
        }

        if ($author !== '') {
            $lines[] = 'author: "'.addcslashes($author, '"').'"';
        }

        return implode("\n", $lines)."\n";
    }
}
