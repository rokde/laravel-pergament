<?php

declare(strict_types=1);

namespace Pergament\Data;

final readonly class SeoMeta
{
    public function __construct(
        public string $title = '',
        public string $description = '',
        public string $keywords = '',
        public string $ogImage = '',
        public string $twitterCard = 'summary_large_image',
        public string $robots = 'index, follow',
        public ?string $canonical = null,
        public ?string $ogType = null,
    ) {}
}
