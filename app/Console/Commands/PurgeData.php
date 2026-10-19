<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 회원/행사 관련 데이터를 물리 삭제(purge)하는 운영용 커맨드.
 *
 * - 기존 tests/Feature/MemberPurgeTest.php 의 삭제 로직을 운영에서도 쓸 수 있도록 Artisan 커맨드로 옮긴 것.
 * - PHPUnit(dev 의존성) 없이 실행된다.
 * - 실제 .env DB 의 데이터를 지우므로 기본적으로 확인 프롬프트를 띄운다. (--force 로 생략)
 *
 * 실행 예:
 *   php artisan data:purge members        # 회원 + 회원 참조 데이터만 삭제 (행사/테이블은 유지)
 *   php artisan data:purge stats          # 방문 통계 스냅샷(event_visit_snapshots)만 삭제 (id 초기화)
 *   php artisan data:purge all            # 행사 + 방문 통계 스냅샷까지 포함한 전체 데이터 삭제
 *   php artisan data:purge all --force    # 확인 없이 즉시 실행
 */
class PurgeData extends Command
{
    protected $signature = 'data:purge
        {scope=all : 삭제 범위 (members | stats | all)}
        {--force : 확인 프롬프트 없이 즉시 실행}';

    protected $description = '회원/행사/방문 통계 데이터를 물리 삭제한다. (members: 회원·참조 데이터, stats: 방문 통계, all: 전체)';

    /**
     * 방문 통계(스냅샷) 테이블. 다른 테이블을 FK 로 참조하지 않는 독립 통계 테이블이라
     * 드라이버와 무관하게 TRUNCATE 로 비워 id(AUTO_INCREMENT/시퀀스)까지 초기화한다.
     *
     * @var list<string>
     */
    private const STATS_TABLES = [
        'event_visit_snapshots',
    ];

    public function handle(): int
    {
        $scope = (string) $this->argument('scope');

        if (! in_array($scope, ['members', 'stats', 'all'], true)) {
            $this->error("scope 값은 members, stats, all 중 하나여야 합니다. (입력: {$scope})");

            return self::FAILURE;
        }

        // FK 의존 그래프를 가진 일반 테이블 (자식 → 부모 순서로 FK 위반 없이 비운다).
        $tables = match ($scope) {
            'members' => [
                // members 를 FK 로 참조하는 자식 테이블부터 삭제 후 members 삭제. (행사/테이블은 유지)
                'event_votes',
                'messages',
                'rotation_seatings',
                'admin_requests',
                'members',
            ],
            'stats' => [
                // 방문 통계만 삭제: 일반 테이블은 손대지 않는다.
            ],
            default => [
                // all: 행사(event_rounds)까지 포함한 전체 초기화.
                // members 는 event_rounds 보다 먼저 삭제한다. (members.event_id = restrict)
                'event_votes',
                'messages',
                'admin_requests',
                'rotation_seatings',
                'rotation_rounds',
                'rotation_tables',
                'members',
                'event_rounds',
            ],
        };

        // 방문 통계(스냅샷) 테이블은 stats · all 범위에서 함께 비운다.
        $statsTables = in_array($scope, ['stats', 'all'], true) ? self::STATS_TABLES : [];

        $allTargets = array_merge($tables, $statsTables);

        $this->warn('⚠️  이 작업은 아래 테이블의 데이터를 물리 삭제합니다. (복구 불가)');
        $this->line('   ' . implode(', ', $allTargets));
        $this->line('   DB: ' . config('database.default') . ' / ' . config('database.connections.' . config('database.default') . '.database'));

        if (! $this->option('force') && ! $this->confirm('정말 삭제하시겠습니까?', false)) {
            $this->info('취소했습니다.');

            return self::SUCCESS;
        }

        $driver = DB::getDriverName();

        // 1) FK 의존 그래프를 가진 일반 테이블.
        foreach ($tables as $table) {
            if ($driver === 'pgsql') {
                // 시퀀스(id) 초기화 + 의존 테이블까지 안전하게 비운다.
                DB::statement("TRUNCATE TABLE {$table} RESTART IDENTITY CASCADE;");
            } else {
                // MySQL 등은 FK 참조가 있으면 TRUNCATE 가 거부되므로 순서대로 delete 한다.
                // (자식 → 부모 순서라 FK 위반 없이 비워진다. id 리셋은 안 되지만 안전.)
                DB::table($table)->delete();
            }

            $this->line("  - {$table} 비움 (남은 row: " . DB::table($table)->count() . ')');
        }

        // 2) 방문 통계(스냅샷) 테이블: FK 참조가 없으므로 드라이버 무관하게 TRUNCATE 로
        //    id(AUTO_INCREMENT/시퀀스)까지 초기화한다.
        foreach ($statsTables as $table) {
            if ($driver === 'pgsql') {
                DB::statement("TRUNCATE TABLE {$table} RESTART IDENTITY CASCADE;");
            } else {
                // MySQL: TRUNCATE 는 AUTO_INCREMENT 를 1로 리셋한다.
                DB::statement("TRUNCATE TABLE {$table};");
            }

            $this->line("  - {$table} 비움 (id 초기화, 남은 row: " . DB::table($table)->count() . ')');
        }

        $label = match ($scope) {
            'members' => '회원',
            'stats'   => '방문 통계',
            default   => '전체',
        };

        $this->info("{$label} 데이터 삭제를 완료했습니다.");

        return self::SUCCESS;
    }
}
