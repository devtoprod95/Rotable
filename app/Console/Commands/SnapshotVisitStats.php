<?php

namespace App\Console\Commands;

use App\Models\EventVisitSnapshot;
use App\Services\VisitStatService;
use Illuminate\Console\Command;

/**
 * 방문 통계 일일 스냅샷 적재 커맨드.
 *
 * - 매일 자정에 실행되어 "그 자정 시점까지"의 누적 방문 통계를 1행으로 저장한다.
 * - snapshot_date 는 방금 끝난 날(전날)로 기록한다. (= 그 날 자정까지의 데이터)
 * - 같은 날짜 스냅샷이 이미 있으면 갱신(upsert)한다. (멱등 — 몇 번 실행해도 데이터 오염 없음)
 *
 * 실행 예:
 *   php artisan rotation:snapshot-visit-stats                       # 오늘 자정 기준(= 어제 하루까지) 집계
 *   php artisan rotation:snapshot-visit-stats --date=2026-10-14     # 2026-10-14 자정 기준(= 2026-10-13 까지) 집계
 *
 * 스케줄 등록 위치: routes/console.php (매일 자정 dailyAt('00:00'))
 */
class SnapshotVisitStats extends Command
{
    protected $signature   = 'rotation:snapshot-visit-stats {--date= : 기준 자정 날짜(YYYY-MM-DD). 생략 시 오늘 자정}';
    protected $description = '방문 통계(총 방문/남/여/1~5번·6번이상 방문자 수)를 일일 스냅샷으로 적재한다.';

    public function __construct(protected VisitStatService $visitStatService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        // 기준 자정: 옵션이 있으면 그 날의 자정, 없으면 오늘 자정(= 어젯밤 24시).
        $cutoff = $this->option('date')
            ? \Illuminate\Support\Carbon::parse($this->option('date'))->startOfDay()
            : now()->startOfDay();

        // 스냅샷 날짜는 그 자정 직전의 날(= 방금 끝난 하루).
        $snapshotDate = $cutoff->copy()->subDay()->toDateString();

        $stats = $this->visitStatService->aggregate($cutoff);

        EventVisitSnapshot::updateOrCreate(
            ['snapshot_date' => $snapshotDate],
            $stats
        );

        channelLog(
            message: "방문 통계 스냅샷 적재 ({$snapshotDate}): 총 {$stats['total_visits']}건",
            channel: 'cron',
        );

        $this->info("방문 통계 스냅샷을 적재했습니다. (기준일: {$snapshotDate}, 총 방문 {$stats['total_visits']}건)");

        return self::SUCCESS;
    }
}
