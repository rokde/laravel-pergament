<?php

declare(strict_types=1);

namespace Pergament\Data;

final readonly class Page
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $title,
        public string $excerpt,
        public string $slug,
        public string $content,
        public ?string $layout = null,
        public array $meta = [],
    ) {}
}
