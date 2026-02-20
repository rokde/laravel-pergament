<?php

declare(strict_types=1);

namespace Pergament\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

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

        $category = $this->option('category') ?? text(
            label: 'What category does this post belong to?',
            hint: 'Leave empty for no category',
        );

        $tags = $this->option('tags') ?? text(
            label: 'What tags should this post have?',
            hint: 'Comma-separated, e.g. "laravel, php, tutorial"',
        );

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

    private function buildFrontMatter(
        string $title,
        string $excerpt,
        string $category,
        string $tags,
        string $author,
    ): string {
        $lines = [];
        $lines[] = 'title: "'.addcslashes($title, '"').'"';
        $lines[] = 'excerpt: "'.addcslashes($excerpt, '"').'"';

        if ($category !== '') {
            $lines[] = 'category: "'.addcslashes($category, '"').'"';
        }

        if ($tags !== '') {
            $tagList = array_map('trim', explode(',', $tags));
            $lines[] = 'tags:';
            foreach ($tagList as $tag) {
                $lines[] = '  - "'.addcslashes($tag, '"').'"';
            }
        }

        if ($author !== '') {
            $lines[] = 'author: "'.addcslashes($author, '"').'"';
        }

        return implode("\n", $lines)."\n";
    }
}
