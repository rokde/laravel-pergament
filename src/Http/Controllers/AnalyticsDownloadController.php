<?php

declare(strict_types=1);

namespace Pergament\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pergament\Services\AnalyticsService;

final class AnalyticsDownloadController
{
    public function __invoke(Request $request, AnalyticsService $analytics): Response
    {
        if (! config('pergament.analytics.download.enabled', false)) {
            abort(404);
        }

        $token = config('pergament.analytics.download.token');

        if (empty($token) || $request->query('token') !== $token) {
            abort(403, 'Forbidden');
        }

        $date = $request->query('date') ?? CarbonImmutable::today()->format('Y-m-d');

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            abort(400, 'Invalid date format. Use Y-m-d.');
        }

        $storagePath = config('pergament.analytics.storage_path') ?? storage_path('pergament/analytics');
        $file = $storagePath.'/'.$date.'.ndjson';

        if (! file_exists($file)) {
            return response('', 404, [
                'Content-Type' => 'application/x-ndjson',
            ]);
        }

        $content = file_get_contents($file);

        return response($content, 200, [
            'Content-Type' => 'application/x-ndjson',
            'Content-Disposition' => 'attachment; filename="analytics-'.$date.'.ndjson"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
