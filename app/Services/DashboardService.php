<?php

namespace App\Services;

use App\Enums\CacheKey;
use App\Enums\HttpStatus;
use App\Models\SiteStatistic;
use App\Models\VisitorLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class DashboardService
{
    public function getVisitorStats(): array
    {
        return Cache::remember(CacheKey::VisitorStats->value, now()->endOfDay(), function () {
            return $this->fetchFromDb();
        });
    }

    public function getOnlineUsers(): array
    {
        $prefix = config('database.redis.options.prefix', '');
        $keys   = (array) Redis::keys('online_admin:*');

        if (empty($keys)) {
            return [];
        }

        return collect($keys)
        ->map(function (string $key) use ($prefix) {
            $data = Redis::hgetall(str_replace($prefix, '', $key));

            if (empty($data)) return null;

            $path = $data['current_page'];

            return [
                'id'                 => $data['id'],
                'name'               => $data['name'],
                'current_page'       => empty($path) || $path === '/' ? '/' : '/' . $path,
                'last_active_at'     => $this->formatLastActive($data['last_active_at']),
                'last_active_at_raw' => $data['last_active_at'],                             // 정렬용
            ];
        })
        ->filter()
        ->sortByDesc('last_active_at_raw') // 최근 활동순 정렬
        ->values()
        ->toArray();
    }

    private function formatLastActive(string $dateTime): string
    {
        $diff = Carbon::parse($dateTime)->diffInSeconds(now());

        return match(true) {
            $diff < 60  => '방금 전',
            $diff < 120 => '1분 전',
            $diff < 180 => '2분 전',
            $diff < 240 => '3분 전',
            $diff < 300 => '4분 전',
            $diff < 360 => '5분 전',
            default     => 'offline',
        };
    }

    private function fetchFromDb(): array
    {
        return [
            'total_visitors'     => SiteStatistic::sum('total_visitors'),
            'today_visitors'     => VisitorLog::today()->count(),
            'yesterday_visitors' => VisitorLog::yesterday()->count(),
        ];
    }

    public function visitorRefresh(): array
    {
        Cache::forget(CacheKey::VisitorStats->value);
        return $this->getVisitorStats();
    }

    public function getHttpStatusStats(): array
    {
        $key       = CacheKey::HTTP_STATUS_COUNTS->forToday();
        $rawCounts = Redis::hgetall($key);                      // ['200' => '1842', '404' => '23', ...]

        $stats  = [];
        $labels = [];

        // Redis에 저장된 데이터 기준으로 순회
        foreach ($rawCounts as $code => $count) {
            $statusCode         = (int) $code;
            $stats[$statusCode] = (int) $count;

            $labels[$statusCode] = HttpStatus::tryFrom($statusCode)?->label() ?? 'Unknown';
        }

        return [
            'httpStats'        => $stats,
            'httpStatusLabels' => $labels,
        ];
    }
}
