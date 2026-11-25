<?php

namespace App\Services\Rotation;

use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 운영 DB의 "모든 행사와 그에 딸린 테이블"을 개발 DB로 통째로 옮긴다. (개발 DB 의 행사 관련 데이터는 모두 지우고 교체)
 *
 * - 운영(production) 환경에서만 동작한다. (그 밖의 환경에서는 isAvailable() 가 false)
 * - 개발 DB 접속 정보는 .env 의 DEV_DB_* 로 받고, config/database.php 의 `dev_sync` 연결을 쓴다.
 * - 개발 DB 의 TABLES 에 적힌 테이블 행(행사 관련 + 방문 통계)을 전부 지운 뒤, 운영 DB 의 같은 테이블 행을 ID 그대로 넣는다.
 *   (관리자 계정·권한·QR·상세페이지, 방문자 원본 로그(visitor_logs: IP/UA) 등은 건드리지 않는다.)
 * - 개발 DB 에서 하나의 트랜잭션으로 처리하므로 중간에 실패하면 전부 롤백된다.
 * - 운영 DB 는 읽기만 한다.
 */
class EventDevSyncService
{
    public const CONNECTION = 'dev_sync';

    /**
     * 복사 대상 테이블. 부모 → 자식 순서. (삭제는 역순)
     * 행사에 종속되는 테이블이 새로 생기면 여기에 추가한다.
     *
     * @var list<string>
     */
    private const TABLES = [
        'event_rounds',
        'event_drinks',
        'rotation_tables',
        'event_rosters',
        'members',
        'rotation_seats',
        'event_votes',
        'messages',
        'admin_requests',
        'event_paybacks',
        'event_costs',
        // 방문 통계 (행사 목록의 방문 통계 · 사이트 통계). 서로 FK 가 없어 순서는 상관없다.
        'event_visit_snapshots',
        'site_statistics',
    ];

    /**
     * 관리자 계정(admins)을 참조하는 컬럼. 개발 DB 에 없는 관리자면 null 로 둔다.
     * (event_rounds.created_by 는 필수라 개발 DB 의 첫 관리자로 대체한다)
     *
     * @var array<string, list<string>>
     */
    private const ADMIN_COLUMNS = [
        'event_rounds'   => ['created_by'],
        'messages'       => ['sender_admin_id'],
        'admin_requests' => ['replied_admin_id'],
        'event_paybacks' => ['transferred_by'],
    ];

    private const CHUNK = 500;

    /**
     * 버튼 노출/실행 가능 여부: 운영 환경이고 개발 DB 정보가 모두 설정돼 있어야 한다.
     */
    public function isAvailable(): bool
    {
        if (! app()->isProduction()) {
            return false;
        }

        $c = config('database.connections.' . self::CONNECTION, []);

        return filled($c['host'] ?? null) && filled($c['database'] ?? null) && filled($c['username'] ?? null);
    }

    /**
     * 모든 행사 데이터를 개발 DB 로 옮긴다. (개발 DB 의 행사 관련 데이터는 먼저 전부 지운다)
     *
     * @return array{isSuccess:bool, msg:string, data?:array<string,int>}
     */
    public function sync(): array
    {
        // 데이터가 많으면 오래 걸릴 수 있어 실행 시간 제한을 풀어 둔다.
        @set_time_limit(0);

        try {
            if (! app()->isProduction()) {
                throw new Exception('운영 환경에서만 사용할 수 있습니다.');
            }
            if (! $this->isAvailable()) {
                throw new Exception('개발 DB 접속 정보(DEV_DB_*)가 설정되지 않았습니다.');
            }
            $this->assertNotSameDatabase();

            $source = DB::connection();
            $dev    = DB::connection(self::CONNECTION);

            // 복사 전에 개발 DB 에 필요한 테이블이 모두 있는지 확인한다. (마이그레이션 누락 조기 발견)
            foreach (self::TABLES as $table) {
                if (! Schema::connection(self::CONNECTION)->hasTable($table)) {
                    throw new Exception("개발 DB에 {$table} 테이블이 없습니다. 개발 DB 마이그레이션을 먼저 실행해 주세요.");
                }
            }

            $counts = $dev->transaction(function () use ($source, $dev): array {
                $adminIds = $dev->table('admins')->pluck('id')->map(fn ($id) => (int) $id)->all();

                // 1) 개발 DB 의 행사 관련 데이터를 전부 삭제 (자식 → 부모).
                foreach (array_reverse(self::TABLES) as $table) {
                    $dev->table($table)->delete();
                }

                // 2) 운영 → 개발 복사 (부모 → 자식).
                $counts = [];
                foreach (self::TABLES as $table) {
                    $counts[$table] = $this->copyTable($source, $dev, $table, $adminIds);
                }

                return $counts;
            });

            $total = array_sum($counts);

            return [
                'isSuccess' => true,
                'msg'       => "개발 DB를 운영 데이터로 교체했습니다. (행사 {$counts['event_rounds']}건, 회원 {$counts['members']}명, 방문 통계 {$counts['event_visit_snapshots']}일치, 총 {$total}행)",
                'data'      => $counts,
            ];
        } catch (\Throwable $th) {
            report($th);

            return helpersFailMessage($this->friendlyMessage($th));
        }
    }

    /**
     * 테이블 1개를 복사하고 복사한 행 수를 돌려준다.
     *
     * @param  list<int>  $adminIds  개발 DB 에 존재하는 관리자 id
     */
    private function copyTable(Connection $source, Connection $dev, string $table, array $adminIds): int
    {
        // 개발 DB 스키마에 있는 컬럼만 복사한다. (개발 DB 마이그레이션이 뒤처져 있어도 공통 컬럼은 옮긴다)
        $devColumns = array_flip(Schema::connection(self::CONNECTION)->getColumnListing($table));
        $adminCols  = self::ADMIN_COLUMNS[$table] ?? [];
        $total      = 0;

        $source->table($table)
            ->chunkById(self::CHUNK, function ($rows) use ($dev, $table, $devColumns, $adminCols, $adminIds, &$total): void {
                $batch = [];
                foreach ($rows as $row) {
                    $data = array_intersect_key((array) $row, $devColumns);
                    foreach ($adminCols as $col) {
                        if (array_key_exists($col, $data)) {
                            $data[$col] = $this->mapAdminId($table, $col, $data[$col], $adminIds);
                        }
                    }
                    $batch[] = $data;
                }
                $dev->table($table)->insert($batch);
                $total += count($batch);
            });

        // PostgreSQL 은 id 를 직접 넣으면 시퀀스가 따라가지 않으므로 맞춰 준다. (MySQL 은 자동)
        if ($total > 0 && $dev->getDriverName() === 'pgsql') {
            $dev->statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), (SELECT COALESCE(MAX(id), 1) FROM {$table}))");
        }

        return $total;
    }

    /**
     * 관리자 id 가 개발 DB 에 있으면 그대로, 없으면 null. (event_rounds.created_by 는 필수라 개발 DB 의 첫 관리자로 대체)
     *
     * @param  list<int>  $adminIds
     */
    private function mapAdminId(string $table, string $column, mixed $value, array $adminIds): ?int
    {
        if ($value !== null && in_array((int) $value, $adminIds, true)) {
            return (int) $value;
        }

        if ($table === 'event_rounds' && $column === 'created_by') {
            if ($adminIds === []) {
                throw new Exception('개발 DB에 관리자 계정이 없어 행사를 복사할 수 없습니다. 개발 DB에 관리자를 먼저 만들어 주세요.');
            }

            return min($adminIds);
        }

        return null;
    }

    /**
     * 개발 DB 설정이 운영(현재) DB 와 같으면 중단한다. (실수로 운영 데이터를 지우는 것을 막는다)
     */
    private function assertNotSameDatabase(): void
    {
        $default = config('database.connections.' . config('database.default'), []);
        $dev     = config('database.connections.' . self::CONNECTION, []);

        $same = strcasecmp((string) ($default['host'] ?? ''), (string) ($dev['host'] ?? '')) === 0
            && (string) ($default['port'] ?? '') === (string) ($dev['port'] ?? '')
            && (string) ($default['database'] ?? '') === (string) ($dev['database'] ?? '');

        if ($same) {
            throw new Exception('개발 DB 설정이 운영 DB와 같습니다. DEV_DB_* 값을 확인해 주세요. (복사를 중단했습니다)');
        }
    }

    /**
     * 사용자에게 보여 줄 오류 문구. 접속 정보 같은 민감한 내용은 노출하지 않는다.
     */
    private function friendlyMessage(\Throwable $th): string
    {
        // 우리가 직접 던진 안내 문구는 그대로 보여 준다. (DB 드라이버 예외는 아래에서 변환)
        if (! $th instanceof QueryException && ! $th instanceof \PDOException) {
            return $th->getMessage();
        }

        $msg = $th->getMessage();

        if (str_contains($msg, 'Duplicate entry') || str_contains($msg, 'duplicate key') || str_contains($msg, 'UNIQUE constraint')) {
            return '개발 DB에 같은 ID의 다른 데이터가 있어 복사하지 못했습니다. (롤백됨) 개발 DB의 해당 ID 데이터를 확인해 주세요.';
        }
        if (str_contains($msg, 'Access denied') || str_contains($msg, 'password authentication failed')) {
            return '개발 DB 계정 정보가 올바르지 않습니다. DEV_DB_USERNAME / DEV_DB_PASSWORD 를 확인해 주세요.';
        }
        if (str_contains($msg, '[2002]') || str_contains($msg, 'could not connect') || str_contains($msg, 'Connection refused') || str_contains($msg, 'getaddrinfo') || str_contains($msg, 'timed out')) {
            return '개발 DB에 접속하지 못했습니다. DEV_DB_* 설정과 네트워크를 확인해 주세요.';
        }

        return '개발 DB 복사 중 오류가 발생했습니다. 서버 로그를 확인해 주세요. (롤백됨)';
    }
}
