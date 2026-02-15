<?php

declare(strict_types=1);

namespace Pergament\Data;

use Carbon\Carbon;

final readonly class BlogPost
{
    /**
     * @param  array<int, Author>  $authors
     * @param  array<int, string>  $tags
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $title,
        public string $excerpt,
        public string $slug,
        public string $content,
        public Carbon $date,
        public ?string $category = null,
        public array $tags = [],
        public array $authors = [],
        public array $meta = [],
    ) {}
}
