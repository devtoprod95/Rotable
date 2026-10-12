<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Carbon\Carbon;
use Cron\CronExpression;
use Illuminate\Http\Request;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    protected Request $request;

    public function __construct(
        Request $request,
        private DashboardService $dashboardService
    )
    {
        $this->request = $request;
    }

    public function index(): View
    {
        $httpStatsArr = $this->dashboardService->getHttpStatusStats();
        $params       = [
            'stats'       => $this->dashboardService->getVisitorStats(),
            'onlineUsers' => $this->dashboardService->getOnlineUsers(),
            'schedules'   => $this->getSchedules(),
            'server'      => [
                'memory_used'  => (int) shell_exec("awk '/MemTotal/{t=$2} /MemAvailable/{a=$2} END{printf \"%d\", (t-a)/1024}' /proc/meminfo"),
                'memory_total' => (int) shell_exec("awk '/MemTotal/{printf \"%d\", $2/1024}' /proc/meminfo"),
                'cpu_usage'    => min(round(sys_getloadavg()[0] / max(1, (int) shell_exec("grep -c ^processor /proc/cpuinfo")) * 100), 100),
                'uptime'       => (function() {
                    $startedAt = Carbon::parse(Cache::get('app_started_at', now()));
                    $seconds   = (int) $startedAt->diffInSeconds(now());
                    $days      = floor($seconds / 86400);
                    $hours     = floor(($seconds % 86400) / 3600);
                    $minutes   = floor(($seconds % 3600) / 60);

                    return ($days > 0 ? "{$days}일 " : '') . ($hours > 0 ? "{$hours}시간 " : '') . "{$minutes}분";
                })(),
                'disk_used'       => round((int) shell_exec("df -m / | tail -1 | awk '{print $3}'") / 1024, 1),
                'disk_total'      => round((int) shell_exec("df -m / | tail -1 | awk '{print $2}'") / 1024, 1),
                'php_version'     => PHP_VERSION,
                'laravel_version' => app()->version(),
                'os'              => php_uname('s'),
            ],
            'httpStats'        => $httpStatsArr['httpStats'],
            'httpStatusLabels' => $httpStatsArr['httpStatusLabels'],
            'logs'             => $this->getLogs(),
        ];

        return view('dashboard', $params);
    }

    protected function getSchedules(): array
    {
        require base_path('routes/console.php');
        $schedule = app(Schedule::class);
        $events   = $schedule->events();
        return collect($events)->map(function ($event) {
            $nextRun = (new CronExpression($event->expression))->getNextRunDate();
            return [
                'name'        => preg_replace("/'.+artisan' /", '', $event->command ?? $event->description ?? '알 수 없음'),
                'description' => $event->description ?? '',
                'expression'  => $event->expression,
                'next_run'    => $nextRun->format('Y-m-d H:i:s'),
                'running'     => $event->mutex->exists($event),
            ];
        })->sortBy([
            fn($a, $b) => $b['running'] <=> $a['running'],  // 실행중이 먼저
            fn($a, $b) => $a['next_run'] <=> $b['next_run'], // 다음 실행 가까운 순
        ])->values()->toArray();
    }


    protected function getLogs(): array
    {
        $logStack = config('logging.default');
        $logFile  = match($logStack) {
            'daily' => storage_path('logs/laravel-' . now()->format('Y-m-d') . '.log'),
            default => storage_path('logs/laravel.log'),
        };

        if (!file_exists($logFile)) {
            return [];
        }

        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+?)(?=\[\d{4}|\z)/s';
        preg_match_all($pattern, file_get_contents($logFile), $matches, PREG_SET_ORDER);

        return collect($matches)
            ->filter(fn($m) => str_starts_with($m[1], now()->format('Y-m-d')))
            ->map(fn($m) => [
                'time'    => Carbon::parse($m[1])->format('H:i:s'),
                'env'     => $m[2],
                'level'   => $m[3],
                'message' => trim(explode("\n", $m[4])[0]),
                'context' => trim($m[4]),
            ])
            ->sortByDesc('time')
            ->values()
            ->toArray();
    }

    public function deleteLog(): JsonResponse
    {
        $logStack = config('logging.default');

        $logFile = match($logStack) {
            'daily'  => storage_path('logs/laravel-' . now()->format('Y-m-d') . '.log'),
            default  => storage_path('logs/laravel.log'),
        };

        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }

        return apiRes(Response::HTTP_OK, helpersSuccessMessage());
    }

}
