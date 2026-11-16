<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 방문 통계 일일 스냅샷에 "행사별 참여 인원(총/남/여/플랫폼별)"을 추가한다.
 *
 * - 행사 수는 계속 늘어나므로 컬럼을 늘리지 않고 JSON 한 컬럼(event_counts)에 행사별 배열로 담는다.
 *   예: [{"event_id":3,"title":"2회차 1:1소개팅","date":"2026.11.13","total":22,"male":12,"female":10,"platform_counts":{"munto":15,"frip":7}}]
 * - 기존 스냅샷은 null (다음 자정 스냅샷부터, 또는 커맨드 재실행 시 채워짐).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_visit_snapshots', function (Blueprint $table) {
            $table->json('event_counts')
                ->nullable()
                ->after('platform_counts')
                ->comment('행사별 참여 인원(총/남/여/플랫폼별) 배열');
        });
    }

    public function down(): void
    {
        Schema::table('event_visit_snapshots', function (Blueprint $table) {
            $table->dropColumn('event_counts');
        });
    }
};
