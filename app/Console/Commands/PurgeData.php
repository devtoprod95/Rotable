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
 *   php artisan data:purge all            # 행사까지 포함한 전체 데이터 삭제
 *   php artisan data:purge all --force    # 확인 없이 즉시 실행
 */
class PurgeData extends Command
{
    protected $signature = 'data:purge
        {scope=all : 삭제 범위 (members | all)}
        {--force : 확인 프롬프트 없이 즉시 실행}';

    protected $description = '회원/행사 관련 데이터를 물리 삭제한다. (members: 회원·참조 데이터, all: 행사까지 전체)';

    public function handle(): int
    {
        $scope = (string) $this->argument('scope');

        if (! in_array($scope, ['members', 'all'], true)) {
            $this->error("scope 값은 members 또는 all 이어야 합니다. (입력: {$scope})");

            return self::FAILURE;
        }

        // 삭제 대상 테이블 (자식 → 부모 순서로 FK 위반 없이 비운다).
        $tables = $scope === 'members'
            ? [
                // members 를 FK 로 참조하는 자식 테이블부터 삭제 후 members 삭제. (행사/테이블은 유지)
                'event_votes',
                'messages',
                'rotation_seatings',
                'admin_requests',
                'members',
            ]
            : [
                // 행사(event_rounds)까지 포함한 전체 초기화.
                // members 는 event_rounds 보다 먼저 삭제한다. (members.event_id = restrict)
                'event_votes',
                'messages',
                'admin_requests',
                'rotation_seatings',
                'rotation_rounds',
                'rotation_tables',
                'members',
                'event_rounds',
            ];

        $this->warn('⚠️  이 작업은 아래 테이블의 데이터를 물리 삭제합니다. (복구 불가)');
        $this->line('   ' . implode(', ', $tables));
        $this->line('   DB: ' . config('database.default') . ' / ' . config('database.connections.' . config('database.default') . '.database'));

        if (! $this->option('force') && ! $this->confirm('정말 삭제하시겠습니까?', false)) {
            $this->info('취소했습니다.');

            return self::SUCCESS;
        }

        $driver = DB::getDriverName();

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

        $this->info(($scope === 'members' ? '회원' : '전체') . ' 데이터 삭제를 완료했습니다.');

        return self::SUCCESS;
    }
}
