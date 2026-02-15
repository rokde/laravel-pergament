<?php

declare(strict_types=1);

namespace Pergament\Data;

use Illuminate\Support\Collection;

final readonly class DocChapter
{
    /**
     * @param  Collection<int, DocPage>  $pages
     */
    public function __construct(
        public string $title,
        public string $slug,
        public Collection $pages,
    ) {}
}
