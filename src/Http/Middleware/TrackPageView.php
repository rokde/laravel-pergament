<?php

declare(strict_types=1);

namespace Pergament\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pergament\Services\AnalyticsService;

final class TrackPageView
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if ($this->shouldTrack($request, $response)) {
            $this->analytics->record($request->getPathInfo(), isBot: $this->isBotRequest($request));
        }

        return $response;
    }

    private function shouldTrack(Request $request, mixed $response): bool
    {
        if (! config('pergament.analytics.enabled', false)) {
            return false;
        }

        if (! $response instanceof Response) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        // Skip markdown export responses — these are not human page views
        $contentType = $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'text/markdown')) {
            return false;
        }

        return true;
    }

    private function isBotRequest(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');

        if ($userAgent === '') {
            return false;
        }

        $botPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'slurp', 'facebookexternalhit',
            'whatsapp', 'twitterbot', 'linkedinbot', 'applebot', 'bingbot',
            'googlebot', 'yandex', 'baidu', 'sogou', 'duckduckbot',
            'ia_archiver', 'archive.org', 'uptimerobot', 'pingdom', 'gtmetrix',
            'lighthouse', 'headlesschrome', 'phantomjs',
        ];

        foreach ($botPatterns as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
