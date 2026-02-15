<?php

declare(strict_types=1);

namespace Pergament\Http\Controllers;

use Illuminate\Http\Response;
use Pergament\Services\FeedService;

final class FeedController
{
    public function __invoke(FeedService $feedService): Response
    {
        $type = config('pergament.blog.feed.type', 'atom');

        if ($type === 'rss') {
            return response($feedService->rss(), 200, [
                'Content-Type' => 'application/rss+xml; charset=UTF-8',
            ]);
        }

        return response($feedService->atom(), 200, [
            'Content-Type' => 'application/atom+xml; charset=UTF-8',
        ]);
    }
}
