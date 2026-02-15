<?php

declare(strict_types=1);

namespace Pergament\Data;

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
        return \Illuminate\Support\Str::slug($this->name);
    }
}
