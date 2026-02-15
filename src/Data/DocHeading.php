<?php

declare(strict_types=1);

namespace Pergament\Data;

final readonly class DocHeading
{
    public function __construct(
        public string $text,
        public string $slug,
        public int $level,
    ) {}
}
