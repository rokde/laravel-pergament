<?php

declare(strict_types=1);

namespace Pergament\Http\Controllers;

use Illuminate\Http\Response;

final class FaviconController
{
    public function show(): Response
    {
        $favicon = (string) config('pergament.favicon', '');
        $path = config('pergament.content_path').'/'.ltrim($favicon, '/');

        abort_unless($favicon !== '' && is_file($path), 404);

        $mimeType = mime_content_type($path) ?: 'application/octet-stream';

        return response((string) file_get_contents($path), 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
