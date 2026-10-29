<?php

namespace App\Console\Commands;

use App\Models\SiteStatistic;
use App\Models\VisitorLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 사이트 방문 통계(site_statistics) 집계 + 오래된 방문 로그(visitor_logs) 정리 커맨드.
 *
 * visitor_logs 는 최근 로그만 보관(오래된 로그는 매일 삭제)하므로,
 * 누적(total_visitors)은 visitor_logs count 로 재계산하면 안 된다(삭제분이 누락됨).
 * 따라서 누적은 "어제 방문 수만큼 증분(increment)"하는 방식으로 보존한다.
 *
 * 처리 순서(순서가 중요):
 *   1) visitor_logs 로 어제/최근7일 방문 수를 집계
 *   2) site_statistics 를 갱신 (누적은 어제분 증분, 어제/주간은 덮어쓰기)
 *   3) 보관 기간(7일)보다 오래된 visitor_logs 삭제
 *
 * 로그를 지우기 "전에" 통계에 반영하므로 데이터 유실이 없다.
 * 삭제 기준은 8일 이상(= 최근 7일 집계에 필요한 범위는 보존)으로 둔다.
 *
 * 멱등성 주의: 누적은 증분 방식이라 같은 날 두 번 실행하면 어제분이 중복 가산될 수 있다.
 * withoutOverlapping + dailyAt 으로 하루 1회만 실행되도록 스케줄에서 보장한다.
 *
 * 스케줄 등록 위치: routes/console.php (매일 자정 dailyAt('00:00'))
 */
class SnapshotSiteStatistics extends Command
{
    /** 방문 로그 보관 일수. 이보다 오래된 로그는 삭제한다. */
    private const RETENTION_DAYS = 7;

    protected $signature   = 'stats:snapshot-site';
    protected $description = '방문 로그(visitor_logs)로 사이트 통계(site_statistics)를 갱신하고, 보관 기간이 지난 오래된 방문 로그를 삭제한다.';

    public function handle(): int
    {
        $yesterday   = now()->subDay()->toDateString();
        $weekAgo     = now()->subDays(self::RETENTION_DAYS)->toDateString();

        // 1) visitor_logs 로 어제/최근7일 방문 수 집계.
        $yesterdayVisitors = VisitorLog::query()->yesterday()->count();
        $weeklyVisitors    = VisitorLog::query()
            ->whereBetween('visited_at', [$weekAgo, $yesterday])
            ->count();

        // 2) site_statistics 갱신. 단일 행으로 유지한다. (가장 오래된 1행을 갱신, 없으면 생성)
        //    누적은 어제분만큼 증분해 보존하고, 어제/주간은 덮어쓴다.
        DB::transaction(function () use ($yesterdayVisitors, $weeklyVisitors): void {
            $row = SiteStatistic::query()->orderBy('id')->lockForUpdate()->first();

            if ($row === null) {
                SiteStatistic::query()->create([
                    'total_visitors'     => $yesterdayVisitors,
                    'yesterday_visitors' => $yesterdayVisitors,
                    'weekly_visitors'    => $weeklyVisitors,
                ]);
            } else {
                $row->total_visitors     = (int) $row->total_visitors + $yesterdayVisitors;
                $row->yesterday_visitors = $yesterdayVisitors;
                $row->weekly_visitors    = $weeklyVisitors;
                $row->save();
            }
        });

        // 3) 보관 기간이 지난(= weekAgo 미만) 오래된 방문 로그 삭제.
        $deleted = VisitorLog::query()
            ->whereDate('visited_at', '<', $weekAgo)
            ->delete();

        channelLog(
            message: "[stats:snapshot-site] 사이트 통계 집계: 어제 {$yesterdayVisitors}건 / 최근7일 {$weeklyVisitors}건, 오래된 로그 {$deleted}건 삭제",
            channel: 'cron',
        );

        $this->info("사이트 통계를 갱신했습니다. (어제 {$yesterdayVisitors}, 최근7일 {$weeklyVisitors}, 삭제 {$deleted}건)");

        return self::SUCCESS;
    }
}
