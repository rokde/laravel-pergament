<?php

declare(strict_types=1);

namespace Pergament\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use League\HTMLToMarkdown\HtmlConverter;

class MarkdownResponse
{
    public function handle(Request $request, Closure $next): mixed
    {
        $shouldConvertToMarkdown = $this->shouldConvertToMarkdown($request);

        if (! $shouldConvertToMarkdown) {
            return $next($request);
        }

        $response = $this->getHtmlResponse($request, $next);

        if (! $this->isHtmlResponse($response)) {
            return $response;
        }

        return $this->convert($response);
    }

    private function shouldConvertToMarkdown(Request $request): bool
    {
        if (str_ends_with($request->getPathInfo(), '.md')) {
            return true;
        }

        if (str_contains($request->header('Accept', ''), 'text/markdown')) {
            return true;
        }

        $userAgent = $request->userAgent() ?? '';
        $patterns = config('pergament.exports.markdown.detection.user_agents', []);
        foreach ($patterns as $pattern) {
            if (str_contains($userAgent, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    private function getHtmlResponse(Request $request, Closure $next): mixed
    {
        $originalAccept = $request->headers->get('Accept');
        $request->headers->set('Accept', 'text/html');

        $response = $next($request);

        $request->headers->set('Accept', $originalAccept);

        return $response;
    }

    private function isHtmlResponse(mixed $response): bool
    {
        if (! $response instanceof Response) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'text/html');
    }

    private function convert(Response $response): Response
    {
        $content = $response->getContent();

        $content = preg_replace('/<header\b[^>]*>.*?<\/header>/is', '', $content);
        $content = preg_replace('/<nav\b[^>]*>.*?<\/nav>/is', '', $content);
        $content = preg_replace('/<footer\b[^>]*>.*?<\/footer>/is', '', $content);
        $content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content);
        $content = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $content);
        $content = preg_replace('/<link\b[^>]*rel=["\']stylesheet["\'][^>]*\/?>/is', '', $content);

        $markdown = resolve(HtmlConverter::class)->convert($content);
        $markdown = strip_tags($markdown);
        $markdown = preg_replace('/[ \t]+$/m', '', $markdown);
        $markdown = preg_replace("/\n{3,}/", "\n\n", $markdown);

        $headers = [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Vary' => 'Accept',
            'X-Robots-Tag' => 'noindex',
            'X-Markdown-Tokens' => (string) $this->estimateTokens($markdown),
        ];

        $contentSignal = $this->buildContentSignalHeader();

        if ($contentSignal !== '') {
            $headers['Content-Signal'] = $contentSignal;
        }

        return new Response($markdown, 200, $headers);
    }

    private function estimateTokens(string $markdown): int
    {
        if ($markdown === '') {
            return 0;
        }

        return (int) ceil(mb_strlen($markdown) / 4);
    }

    private function buildContentSignalHeader(): string
    {
        $signals = config('pergament.exports.markdown.content_signals', []);

        if (empty($signals)) {
            return '';
        }

        return collect($signals)
            ->map(fn (string $value, string $key) => "{$key}={$value}")
            ->implode(', ');
    }
}
