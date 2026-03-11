<?php

declare(strict_types=1);

namespace Pergament\Services;

use Carbon\CarbonImmutable;

final readonly class ContentStatisticsService
{
    /**
     * Compute content statistics based on enabled settings.
     *
     * @param  array<string, bool>  $enabledStats
     * @return array<string, mixed>
     */
    public function compute(string $content, ?string $filePath, array $enabledStats): array
    {
        $stats = [];
        $plainText = null;

        if (! empty($enabledStats['reading_time']) || ! empty($enabledStats['word_count'])) {
            $plainText = $this->stripMarkdown($content);
            $wordCount = str_word_count($plainText);
        }

        if (! empty($enabledStats['reading_time'])) {
            $stats['reading_time'] = (int) max(1, ceil($wordCount / 200));
        }

        if (! empty($enabledStats['word_count'])) {
            $stats['word_count'] = $wordCount;
        }

        if (! empty($enabledStats['character_count'])) {
            $plainText ??= $this->stripMarkdown($content);
            $stats['character_count'] = mb_strlen(preg_replace('/\s+/', '', $plainText));
        }

        if (! empty($enabledStats['paragraph_count'])) {
            $paragraphs = preg_split('/\n\s*\n/', trim($content));
            $stats['paragraph_count'] = count(array_filter($paragraphs, fn (string $p): bool => trim($p) !== ''));
        }

        if (! empty($enabledStats['last_modified']) && $filePath !== null && file_exists($filePath)) {
            $stats['last_modified'] = CarbonImmutable::createFromTimestamp(filemtime($filePath));
        }

        if (! empty($enabledStats['content_age']) && $filePath !== null && file_exists($filePath)) {
            $stats['content_age'] = CarbonImmutable::createFromTimestamp(filemtime($filePath));
        }

        return $stats;
    }

    private function stripMarkdown(string $content): string
    {
        $text = preg_replace('/```[\s\S]*?```/', '', $content);
        $text = preg_replace('/`[^`]+`/', '', $text);
        $text = preg_replace('/!\[[^\]]*\]\([^)]+\)/', '', $text);
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);
        $text = preg_replace('/^#{1,6}\s+/m', '', $text);
        $text = preg_replace('/[*_]{1,3}([^*_]+)[*_]{1,3}/', '$1', $text);
        $text = preg_replace('/^>\s*/m', '', $text);
        $text = preg_replace('/^[-*_]{3,}\s*$/m', '', $text);
        $text = preg_replace('/^---\s*$/m', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
