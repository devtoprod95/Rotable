<?php

namespace App\Http\Middleware;

use App\Enums\CacheKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class TrackHttpStatusCountsMiddleware
{
    private array $botKeywords = [
        'bot', 'crawler', 'spider', 'curl', 'wget',
        'slurp', 'bingbot', 'googlebot', 'yandex',
    ];

    private array $nonBrowserKeywords = [
        'python', 'python-requests', 'aiohttp', 'httpx',
        'postmanruntime', 'insomnia', 'okhttp', 'java/',
        'go-http-client', 'libwww-perl', 'guzzlehttp', 'php/',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($this->shouldSkip($request, $response)) {
            return;
        }

        $key    = CacheKey::HTTP_STATUS_COUNTS->forToday();
        $status = (string) $response->getStatusCode();
        Redis::hincrby($key, $status, 1);

        // 자정 넘으면 자동 소멸 (오늘 자정까지 남은 초)
        $ttl = now()->secondsUntilEndOfDay();
        Redis::expire($key, $ttl);
    }

    private function shouldSkip(Request $request, Response $response): bool
    {
        $excludeExtensions = [
            'ico', 'png', 'jpg', 'jpeg', 'gif', 'svg',
            'css', 'js', 'map', 'woff', 'woff2', 'ttf', 'otf',
            'txt', 'xml'
        ];

        if (!$request->isMethod('GET')) {
            return true;
        }

        $path = strtolower($request->path());

        if ($path === 'up') {
            return true;
        }

        if (str_starts_with($path, '_debugbar')) {
            return true;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (in_array(strtolower($extension), $excludeExtensions)) {
            return true;
        }

        if (str_contains($path, 'favicon.ico')) {
            return true;
        }

        if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return true;
        }

        if ($this->isBot($request)) {
            return true;
        }

        if (!$this->isWebBrowser($request)) {
            return true;
        }

        $fetchDest = strtolower((string) $request->header('Sec-Fetch-Dest', ''));
        if ($fetchDest !== '' && $fetchDest !== 'document') {
            return true;
        }

        if (
            $response->isRedirection()
            && str_contains(strtolower((string) $response->headers->get('Location', '')), '/admin/login')
            && !$request->routeIs('admin.show', 'admin.login')
        ) {
            return true;
        }

        if (!$request->route()) {
            return true;
        }

        return false;
    }

    private function isBot(Request $request): bool
    {
        $ua = strtolower($request->userAgent() ?? '');

        foreach ($this->botKeywords as $keyword) {
            if (str_contains($ua, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function isWebBrowser(Request $request): bool
    {
        $ua = strtolower(trim($request->userAgent() ?? ''));

        if ($ua === '' || !str_contains($ua, 'mozilla/')) {
            return false;
        }

        foreach ($this->nonBrowserKeywords as $keyword) {
            if (str_contains($ua, $keyword)) {
                return false;
            }
        }

        return str_contains($ua, 'chrome/')
            || str_contains($ua, 'safari/')
            || str_contains($ua, 'firefox/')
            || str_contains($ua, 'edg/')
            || str_contains($ua, 'opr/');
    }

}
