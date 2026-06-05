<?php

declare(strict_types=1);

namespace Pergament\Support;

final class SidecarAssets
{
    /**
     * Read the same-named `.css` and `.js` files sitting next to a Markdown file.
     *
     * @return array{styles: ?string, scripts: ?string}
     */
    public static function forMarkdownFile(?string $mdPath): array
    {
        if ($mdPath === null || ! str_ends_with($mdPath, '.md')) {
            return ['styles' => null, 'scripts' => null];
        }

        $base = substr($mdPath, 0, -3);

        return [
            'styles' => self::read($base.'.css'),
            'scripts' => self::read($base.'.js'),
        ];
    }

    private static function read(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $trimmed = trim($contents);

        return $trimmed === '' ? null : $trimmed;
    }
}
