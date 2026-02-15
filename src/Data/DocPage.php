<?php

declare(strict_types=1);

namespace Pergament\Data;

final readonly class DocPage
{
    public function __construct(
        public string $title,
        public string $excerpt,
        public string $slug,
        public string $content,
        /** @var array<string, mixed> */
        public array $meta = [],
    ) {}
}
