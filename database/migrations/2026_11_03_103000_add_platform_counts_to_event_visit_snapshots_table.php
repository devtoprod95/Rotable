<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 방문 통계 일일 스냅샷에 "신청 플랫폼별 방문 수"를 추가한다.
 *
 * - 방문 = members 레코드 1건 기준. (성별 집계와 동일한 단위)
 * - 플랫폼은 앞으로 늘어날 수 있으므로, 컬럼을 플랫폼마다 추가하지 않고
 *   JSON 한 컬럼(platform_counts)에 { "munto": N, "frip": N, ... } 형태로 담는다.
 *   (플랫폼 추가 시 Member::platformOptions() 만 늘리면 집계/표시가 자동 확장)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_visit_snapshots', function (Blueprint $table) {
            $table->json('platform_counts')
                ->nullable()
                ->after('total_female')
                ->comment('신청 플랫폼별 방문 수. 예: {"munto":12,"frip":8}');
        });
    }

    public function down(): void
    {
        Schema::table('event_visit_snapshots', function (Blueprint $table) {
            $table->dropColumn('platform_counts');
        });
    }
};
