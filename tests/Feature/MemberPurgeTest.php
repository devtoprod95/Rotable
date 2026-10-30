<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MemberPurgeTest extends TestCase
{
    /**
     * 회원(members)과 연관 데이터를 전부 하드 삭제한다. (테이블 유지, row 만 물리 삭제)
     *
     * 외래키 제약을 끄지 않고, members 를 참조(FK)하는 자식 테이블을 먼저 삭제한 뒤
     * 마지막에 members 를 삭제하여 FK 위반 없이 순서대로 비운다.
     * 쿼리 빌더 delete() 는 soft delete 를 거치지 않는 물리 삭제다.
     *
     * ⚠️ phpunit.xml 의 sqlite(:memory:) 강제를 제거했으므로 테스트는 .env 의 실제 DB 에 연결된다.
     *    즉 이 테스트는 실제 DB의 데이터를 지운다. (RefreshDatabase 미사용)
     *
     * 실행:
     *   php artisan test --filter=test_purge_all_members
     *   docker compose exec php php artisan test --filter=test_purge_all_members
     */
    public function test_purge_all_members(): void
    {
        // members 를 FK 로 참조하는 자식 테이블부터 삭제한다. (선행 삭제 순서)
        //  - event_votes     : voter_id, target_id
        //  - messages        : sender_id, receiver_id
        //  - rotation_seats  : member_id
        //  - admin_requests  : member_id
        $childTables = [
            'event_votes',
            'messages',
            'rotation_seats',
            'admin_requests',
        ];

        // 1) 자식 테이블 먼저 삭제 (FK 제약에 걸리지 않도록).
        foreach ($childTables as $table) {
            DB::table($table)->delete();
        }

        // 2) 부모 테이블(members) 삭제.
        DB::table('members')->delete();

        // 전부 비워졌는지 확인.
        $this->assertSame(0, DB::table('members')->count());
        foreach ($childTables as $table) {
            $this->assertSame(0, DB::table($table)->count());
        }
    }

    /**
     * 전체 기능 초기화: 행사(event_rounds)와 그에 딸린 모든 데이터 + 회원까지 전부 비운다.
     *
     * 대상 테이블 (FK 의존 그래프):
     *   event_rounds (최상위)
     *     ├─ event_votes     (event_id, members FK)
     *     ├─ messages        (event_id, members FK)
     *     ├─ admin_requests  (event_id, members FK)
     *     ├─ members         (event_id, onDelete=restrict → event_rounds 보다 먼저 삭제 필요)
     *     ├─ rotation_tables (event_id)
     *     └─ rotation_seats  (event_id, rotation_table_id, member_id)
     *
     * 자식 → 부모 순서로 삭제하여 FK 위반을 피한다. (제약을 끄지 않음)
     * pgsql 은 TRUNCATE ... RESTART IDENTITY CASCADE 로 id 까지 초기화, 그 외 드라이버는 truncate 사용.
     *
     * ⚠️ 실제 .env DB 의 로테이션/회원 데이터를 전부 삭제한다. (RefreshDatabase 미사용)
     *
     * 실행:
     *   php artisan test --filter=test_purge_all_data
     *   docker compose exec php php artisan test --filter=test_purge_all_data
     */
    public function test_purge_all_data(): void
    {
        // 자식 → 부모 순서. (members 는 event_rounds 보다 먼저: members.event_id 가 restrict)
        $tables = [
            'event_votes',
            'messages',
            'admin_requests',
            'rotation_seats',
            'rotation_tables',
            'members',
            'event_rounds',
        ];

        $driver = DB::getDriverName();

        foreach ($tables as $table) {
            if ($driver === 'pgsql') {
                // 시퀀스(id) 초기화 + 의존 테이블까지 안전하게 비운다.
                DB::statement("TRUNCATE TABLE {$table} RESTART IDENTITY CASCADE;");
            } else {
                // MySQL 등은 FK 참조가 있으면 TRUNCATE 가 거부되므로, 순서대로 delete 한다.
                // (자식 → 부모 순서라 FK 위반 없이 비워진다. id 리셋은 안 되지만 안전.)
                DB::table($table)->delete();
            }
        }

        // 전부 비워졌는지 확인.
        foreach ($tables as $table) {
            $this->assertSame(0, DB::table($table)->count(), "{$table} 가 비워지지 않았습니다.");
        }
    }
}
