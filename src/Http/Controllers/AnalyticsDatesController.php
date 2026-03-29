<?php

declare(strict_types=1);

namespace Pergament\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pergament\Services\AnalyticsService;

final class AnalyticsDatesController
{
    public function __invoke(Request $request, AnalyticsService $analytics): JsonResponse
    {
        if (! config('pergament.analytics.download.enabled', false)) {
            abort(404);
        }

        $token = config('pergament.analytics.download.token');

        if (empty($token) || $request->query('token') !== $token) {
            abort(403, 'Forbidden');
        }

        return response()->json($analytics->getAvailableDates());
    }
}
