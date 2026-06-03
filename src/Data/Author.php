<?php

declare(strict_types=1);

namespace Pergament\Data;

use Illuminate\Support\Str;

final readonly class Author
{
    public function __construct(
        public string $name,
        public ?string $email = null,
        public ?string $url = null,
        public ?string $avatar = null,
    ) {}

    public function slug(): string
    {
        return Str::slug($this->name);
    }
}
