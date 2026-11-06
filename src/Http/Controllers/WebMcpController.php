<?php

declare(strict_types=1);

namespace Pergament\Http\Controllers;

use Illuminate\Http\Response;
use Pergament\Support\WebMcpManifest;

final class WebMcpController
{
    public function manifest(): Response
    {
        return response(WebMcpManifest::toJson(route('pergament.search')), 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}
