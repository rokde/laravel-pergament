<?php

declare(strict_types=1);

namespace Pergament\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Pergament\Services\AnalyticsService;

final class AnalyticsCommand extends Command
{
    protected $signature = 'pergament:analytics
                            {--date= : Show detailed stats for a specific date (Y-m-d), defaults to today}
                            {--days=30 : Number of days to include in the summary}
                            {--summary : Show multi-day summary instead of single-day detail}
                            {--remote= : Base URL of the remote site to fetch analytics from}
                            {--token= : Authentication token for the remote download endpoint}';

    protected $description = 'View Pergament analytics (page view counts by URL)';

    public function handle(AnalyticsService $analytics): int
    {
        if ($this->option('remote')) {
            return $this->handleRemote();
        }

        if (! config('pergament.analytics.enabled', false)) {
            $this->warn('Analytics is disabled. Enable it in config: pergament.analytics.enabled = true');

            return self::FAILURE;
        }

        if ($this->option('summary')) {
            return $this->showSummary($analytics);
        }

        return $this->showDailyDetail($analytics);
    }

    private function handleRemote(): int
    {
        $baseUrl = rtrim((string) $this->option('remote'), '/');
        $token = $this->option('token') ?? '';

        if (empty($token)) {
            $this->error('--token is required when using --remote.');

            return self::FAILURE;
        }

        $basePrefix = config('pergament.url_prefix', '');
        $basePath = $basePrefix !== '' ? '/'.trim($basePrefix, '/') : '';

        if ($this->option('summary')) {
            return $this->showRemoteSummary($baseUrl, $basePath, $token);
        }

        $date = $this->option('date') ?? CarbonImmutable::today()->format('Y-m-d');

        return $this->showRemoteDailyDetail($baseUrl, $basePath, $token, $date);
    }

    private function showRemoteDailyDetail(string $baseUrl, string $basePath, string $token, string $date): int
    {
        $url = $baseUrl.$basePath.'/analytics/download?date='.$date.'&token='.$token;

        $this->line("Fetching analytics from remote for {$date}…");

        $response = Http::get($url);

        if ($response->status() === 404) {
            $this->line("No analytics data for {$date} on remote.");

            return self::SUCCESS;
        }

        if (! $response->successful()) {
            $this->error("Remote returned HTTP {$response->status()}. Check the URL and token.");

            return self::FAILURE;
        }

        $hits = $this->parseNdjson($response->body());

        if (empty($hits)) {
            $this->line("No analytics data for {$date}.");

            return self::SUCCESS;
        }

        return $this->displayDailyDetail($hits, $date);
    }

    private function showRemoteSummary(string $baseUrl, string $basePath, string $token, int $days = 30): int
    {
        $days = max(1, (int) $this->option('days'));
        $today = CarbonImmutable::today();
        $summary = [];

        $this->line("Fetching {$days}-day summary from remote…");

        for ($i = 0; $i < $days; $i++) {
            $date = $today->subDays($i)->format('Y-m-d');
            $url = $baseUrl.$basePath.'/analytics/download?date='.$date.'&token='.$token;

            $response = Http::get($url);

            if ($response->status() === 404 || ! $response->successful()) {
                continue;
            }

            $hits = $this->parseNdjson($response->body());

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

        if (empty($summary)) {
            $this->line("No analytics data for the last {$days} day(s) on remote.");

            return self::SUCCESS;
        }

        return $this->displaySummary($summary, $days);
    }

    private function showDailyDetail(AnalyticsService $analytics): int
    {
        $date = $this->option('date') ?? CarbonImmutable::today()->format('Y-m-d');
        $hits = $analytics->getHits($date);

        if (empty($hits)) {
            $this->line("No analytics data for {$date}.");

            return self::SUCCESS;
        }

        return $this->displayDailyDetail($hits, $date);
    }

    private function showSummary(AnalyticsService $analytics): int
    {
        $days = max(1, (int) $this->option('days'));
        $summary = $analytics->getSummary($days);

        if (empty($summary)) {
            $this->line("No analytics data for the last {$days} day(s).");

            return self::SUCCESS;
        }

        return $this->displaySummary($summary, $days);
    }

    /**
     * @param  array<int, array{url: string, timestamp: string, is_bot: bool}>  $hits
     */
    private function displayDailyDetail(array $hits, string $date): int
    {
        $total = count($hits);
        $bots = count(array_filter($hits, fn (array $h): bool => $h['is_bot'] ?? false));
        $users = $total - $bots;

        $this->info("Page views for {$date} — {$total} total ({$users} users, {$bots} bots)");
        $this->newLine();

        $counts = [];
        foreach ($hits as $hit) {
            $key = $hit['url'].'|'.($hit['is_bot'] ? 'bot' : 'user');
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $rows = [];
        foreach ($counts as $key => $count) {
            [$url, $type] = explode('|', $key, 2);
            $rows[] = [$url, $type, $count];
        }

        usort($rows, fn (array $a, array $b): int => $b[2] <=> $a[2]);

        $this->table(['URL', 'Type', 'Views'], $rows);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array{total: int, bots: int, users: int, unique_urls: int}>  $summary
     */
    private function displaySummary(array $summary, int $days): int
    {
        $this->info("Analytics summary — last {$days} day(s)");
        $this->newLine();

        $rows = [];
        foreach ($summary as $date => $stats) {
            $rows[] = [$date, $stats['total'], $stats['users'], $stats['bots'], $stats['unique_urls']];
        }

        $this->table(['Date', 'Total', 'Users', 'Bots', 'Unique URLs'], $rows);

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{url: string, timestamp: string, is_bot: bool}>
     */
    private function parseNdjson(string $body): array
    {
        $hits = [];

        foreach (explode("\n", trim($body)) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            /** @var array{url: string, timestamp: string, is_bot: bool}|null $data */
            $data = json_decode($line, true);

            if ($data !== null) {
                $hits[] = $data;
            }
        }

        return $hits;
    }
}
