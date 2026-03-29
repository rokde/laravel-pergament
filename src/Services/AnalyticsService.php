<?php

declare(strict_types=1);

namespace Pergament\Services;

use Carbon\CarbonImmutable;

final class AnalyticsService
{
    /**
     * Record a page view. Stores the URL path, timestamp, and whether the request came from a bot.
     */
    public function record(string $url, ?CarbonImmutable $timestamp = null, bool $isBot = false): void
    {
        $storagePath = $this->storagePath();
        $timestamp ??= CarbonImmutable::now();
        $date = $timestamp->format('Y-m-d');
        $file = $storagePath.'/'.$date.'.ndjson';

        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $entry = json_encode([
            'url' => $url,
            'timestamp' => $timestamp->toIso8601String(),
            'is_bot' => $isBot,
        ])."\n";

        file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get all recorded hits for a given date (Y-m-d format).
     *
     * @return array<int, array{url: string, timestamp: string, is_bot: bool}>
     */
    public function getHits(string $date): array
    {
        $file = $this->storagePath().'/'.$date.'.ndjson';

        if (! file_exists($file)) {
            return [];
        }

        $hits = [];

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            /** @var array{url: string, timestamp: string}|null $data */
            $data = json_decode($line, true);

            if ($data !== null) {
                $hits[] = $data;
            }
        }

        return $hits;
    }

    /**
     * Get per-URL hit counts for a given date, optionally filtered by type.
     *
     * @return array<string, int>
     */
    public function getHitsByUrl(string $date, ?bool $botFilter = null): array
    {
        $counts = [];

        foreach ($this->getHits($date) as $hit) {
            if ($botFilter !== null && ($hit['is_bot'] ?? false) !== $botFilter) {
                continue;
            }

            $url = $hit['url'];
            $counts[$url] = ($counts[$url] ?? 0) + 1;
        }

        arsort($counts);

        return $counts;
    }

    /**
     * Get a summary of total hits and unique URLs per day for the last N days.
     *
     * @return array<string, array{total: int, bots: int, users: int, unique_urls: int}>
     */
    public function getSummary(int $days = 30): array
    {
        $summary = [];
        $today = CarbonImmutable::today();

        for ($i = 0; $i < $days; $i++) {
            $date = $today->subDays($i)->format('Y-m-d');
            $hits = $this->getHits($date);

            if (empty($hits)) {
                continue;
            }

            $bots = count(array_filter($hits, fn (array $h): bool => $h['is_bot'] ?? false));

            $summary[$date] = [
                'total' => count($hits),
                'bots' => $bots,
                'users' => count($hits) - $bots,
                'unique_urls' => count(array_unique(array_column($hits, 'url'))),
            ];
        }

        ksort($summary);

        return $summary;
    }

    /**
     * List all dates that have recorded analytics data.
     *
     * @return list<string>
     */
    public function getAvailableDates(): array
    {
        $storagePath = $this->storagePath();

        if (! is_dir($storagePath)) {
            return [];
        }

        $dates = [];

        foreach (glob($storagePath.'/*.ndjson') as $file) {
            $dates[] = basename($file, '.ndjson');
        }

        sort($dates);

        return $dates;
    }

    /**
     * Merge hits from an NDJSON string into the local file for the given date.
     * Entries are deduplicated by url+timestamp — already-present hits are skipped.
     * Returns the number of new entries written.
     */
    public function mergeFromNdjson(string $date, string $ndjson): int
    {
        $storagePath = $this->storagePath();

        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $file = $storagePath.'/'.$date.'.ndjson';

        // Build a set of existing keys to avoid duplicates
        $existing = [];
        if (file_exists($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                /** @var array{url: string, timestamp: string}|null $data */
                $data = json_decode($line, true);
                if ($data !== null) {
                    $existing[($data['url'] ?? '').'|'.($data['timestamp'] ?? '')] = true;
                }
            }
        }

        $appended = 0;
        $handle = fopen($file, 'a');

        if ($handle === false) {
            return 0;
        }

        flock($handle, LOCK_EX);

        foreach (explode("\n", trim($ndjson)) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            /** @var array{url: string, timestamp: string}|null $data */
            $data = json_decode($line, true);

            if ($data === null) {
                continue;
            }

            $key = ($data['url'] ?? '').'|'.($data['timestamp'] ?? '');

            if (isset($existing[$key])) {
                continue;
            }

            fwrite($handle, $line."\n");
            $existing[$key] = true;
            $appended++;
        }

        flock($handle, LOCK_UN);
        fclose($handle);

        return $appended;
    }

    private function storagePath(): string
    {
        return config('pergament.analytics.storage_path') ?? storage_path('pergament/analytics');
    }
}
