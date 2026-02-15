<?php

declare(strict_types=1);

namespace Pergament\Http\Controllers;

use Illuminate\Http\Response;

final class PwaController
{
    public function manifest(): Response
    {
        $pwa = config('pergament.pwa', []);
        $siteUrl = config('pergament.site.url', '');

        $manifest = [
            'name' => $pwa['name'] ?? config('pergament.site.name', ''),
            'short_name' => $pwa['short_name'] ?? config('pergament.site.name', ''),
            'description' => $pwa['description'] ?? '',
            'start_url' => '/',
            'display' => $pwa['display'] ?? 'standalone',
            'theme_color' => $pwa['theme_color'] ?? '#ffffff',
            'background_color' => $pwa['background_color'] ?? '#ffffff',
            'icons' => $pwa['icons'] ?? [],
        ];

        return response(json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 200, [
            'Content-Type' => 'application/manifest+json',
        ]);
    }

    public function serviceWorker(): Response
    {
        $siteName = config('pergament.site.name', 'pergament');
        $version = '1';

        $js = <<<JS
        const CACHE_NAME = '{$siteName}-v{$version}';
        const OFFLINE_URL = '/';

        self.addEventListener('install', (event) => {
            event.waitUntil(
                caches.open(CACHE_NAME).then((cache) => cache.add(OFFLINE_URL))
            );
            self.skipWaiting();
        });

        self.addEventListener('activate', (event) => {
            event.waitUntil(
                caches.keys().then((keys) =>
                    Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
                )
            );
            self.clients.claim();
        });

        self.addEventListener('fetch', (event) => {
            if (event.request.mode !== 'navigate') return;

            event.respondWith(
                fetch(event.request).catch(() => caches.match(OFFLINE_URL))
            );
        });
        JS;

        return response($js, 200, [
            'Content-Type' => 'application/javascript',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
