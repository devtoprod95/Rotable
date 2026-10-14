<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 방문 통계 집계(VisitStatService) 성능을 위한 인덱스 추가.
     * - phone: aggregate() 의 GROUP BY phone, visitOrdinalMap() 의 whereIn(phone) 에 사용.
     *   (기존 unique(event_id, phone) 복합 인덱스는 phone 단독 조회/그룹핑에는 활용되지 않음)
     * - created_at: aggregate() 의 "기준 자정 미만(created_at < cutoff)" 범위 조건에 사용.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (! $this->hasIndex('members', 'members_phone_index')) {
                $table->index('phone');
            }
            if (! $this->hasIndex('members', 'members_created_at_index')) {
                $table->index('created_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if ($this->hasIndex('members', 'members_phone_index')) {
                $table->dropIndex('members_phone_index');
            }
            if ($this->hasIndex('members', 'members_created_at_index')) {
                $table->dropIndex('members_created_at_index');
            }
        });
    }

    /**
     * 주어진 테이블에 특정 이름의 인덱스가 이미 존재하는지 확인한다. (중복 생성 방지)
     */
    private function hasIndex(string $table, string $index): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $existing) {
            if (($existing['name'] ?? null) === $index) {
                return true;
            }
        }

        return false;
    }
};
