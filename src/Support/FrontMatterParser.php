<?php

declare(strict_types=1);

namespace Pergament\Support;

use Illuminate\Support\Arr;

final class FrontMatterParser
{
    /**
     * Parse a markdown file with optional YAML front matter.
     *
     * @return array{attributes: array<string, mixed>, body: string}
     */
    public function parse(string $raw): array
    {
        $raw = mb_ltrim($raw);

        if (! str_starts_with($raw, '---')) {
            return ['attributes' => [], 'body' => $raw];
        }

        $parts = preg_split('/^---\s*$/m', $raw, 3);

        if ($parts === false || count($parts) < 3) {
            return ['attributes' => [], 'body' => $raw];
        }

        $yamlString = mb_trim($parts[1]);
        $body = mb_ltrim($parts[2]);

        $attributes = $this->parseYaml($yamlString);

        return ['attributes' => $attributes, 'body' => $body];
    }

    /**
     * Expand dot-notated keys into nested arrays and merge with defaults.
     *
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function mergeWithDotNotation(array $defaults, array $overrides): array
    {
        $expanded = [];

        foreach ($overrides as $key => $value) {
            Arr::set($expanded, $key, $value);
        }

        return array_replace_recursive($defaults, $expanded);
    }

    /**
     * Simple YAML parser for front matter (handles common patterns).
     *
     * @return array<string, mixed>
     */
    private function parseYaml(string $yaml): array
    {
        $result = [];
        $lines = explode("\n", $yaml);
        $currentKey = null;
        $isCollectingList = false;

        foreach ($lines as $line) {
            $trimmed = mb_trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (preg_match('/^(\S[\w.]+)\s*:\s*(.*)$/', $trimmed, $match)) {
                $key = $match[1];
                $value = mb_trim($match[2]);
                $currentKey = $key;
                $isCollectingList = false;

                if ($value === '') {
                    $result[$key] = [];
                    $isCollectingList = true;
                } elseif (preg_match('/^\[(.+)\]$/', $value, $arrayMatch)) {
                    $result[$key] = array_map(
                        fn (string $item): string => mb_trim(mb_trim($item), '\'"'),
                        explode(',', $arrayMatch[1]),
                    );
                } else {
                    $result[$key] = $this->castValue($value);
                }
            } elseif ($isCollectingList && $currentKey !== null && preg_match('/^\s*-\s+(.+)$/', $trimmed, $match)) {
                $result[$currentKey][] = $this->castValue(mb_trim($match[1]));
            }
        }

        return $result;
    }

    private function castValue(string $value): string|int|float|bool|null
    {
        $unquoted = preg_replace('/^["\'](.+)["\']$/', '$1', $value);
        if ($unquoted !== $value) {
            return $unquoted;
        }

        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }
        if ($value === 'null') {
            return null;
        }
        if (is_numeric($value) && ! str_contains($value, '.')) {
            return (int) $value;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }

        return $value;
    }
}
