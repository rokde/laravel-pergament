<?php

declare(strict_types=1);

namespace Pergament\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Pergament\Services\AnalyticsService;

final class AnalyticsCommand extends Command
{
    protected $signature = 'pergament:analytics
                            {--date= : Show detailed stats for a specific date (Y-m-d), defaults to today}
                            {--days=30 : Number of days to include in the summary}
                            {--summary : Show multi-day summary instead of single-day detail}';

    protected $description = 'View Pergament analytics (page view counts by URL)';

    public function handle(AnalyticsService $analytics): int
    {
        if (! config('pergament.analytics.enabled', false)) {
            $this->warn('Analytics is disabled. Enable it in config: pergament.analytics.enabled = true');

            return self::FAILURE;
        }

        if ($this->option('summary')) {
            return $this->showSummary($analytics);
        }

        return $this->showDailyDetail($analytics);
    }

    private function showDailyDetail(AnalyticsService $analytics): int
    {
        $date = $this->option('date') ?? CarbonImmutable::today()->format('Y-m-d');
        $hitsByUrl = $analytics->getHitsByUrl($date);

        if (empty($hitsByUrl)) {
            $this->line("No analytics data for {$date}.");

            return self::SUCCESS;
        }

        $total = array_sum($hitsByUrl);
        $this->info("Page views for {$date} — {$total} total");
        $this->newLine();

        $rows = [];
        foreach ($hitsByUrl as $url => $count) {
            $rows[] = [$url, $count];
        }

        $this->table(['URL', 'Views'], $rows);

        return self::SUCCESS;
    }

    private function showSummary(AnalyticsService $analytics): int
    {
        $days = max(1, (int) $this->option('days'));
        $summary = $analytics->getSummary($days);

        if (empty($summary)) {
            $this->line("No analytics data for the last {$days} day(s).");

            return self::SUCCESS;
        }

        $this->info("Analytics summary — last {$days} day(s)");
        $this->newLine();

        $rows = [];
        foreach ($summary as $date => $stats) {
            $rows[] = [$date, $stats['total'], $stats['unique_urls']];
        }

        $this->table(['Date', 'Total Views', 'Unique URLs'], $rows);

        return self::SUCCESS;
    }
}
