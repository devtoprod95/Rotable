<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 방문 통계 일일 스냅샷에 "노쇼(신청했으나 미참석) 인원"을 추가한다.
 *
 * - 노쇼 기준: 체크인 명단(event_rosters) 중 checked_in_at 이 null 인 행. (신청했으나 당일 미참석)
 * - 방문 통계가 성별·플랫폼으로 분류되듯, 노쇼도 같은 축으로 나눠 저장한다.
 *   · no_show_total / no_show_male / no_show_female : 성별 집계 (roster.gender 기준, F 외는 남으로 귀속)
 *   · no_show_platform_counts : 플랫폼별 노쇼 수. platform_counts 와 동일하게 JSON 으로 담아
 *     플랫폼 확장 시 Member::platformOptions() 만 늘리면 집계/표시가 자동 확장되게 한다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_visit_snapshots', function (Blueprint $table) {
            $table->unsignedInteger('no_show_total')
                ->default(0)
                ->after('visit_6_plus_female')
                ->comment('노쇼(미참석) 총 인원 — event_rosters.checked_in_at is null');
            $table->unsignedInteger('no_show_male')
                ->default(0)
                ->after('no_show_total')
                ->comment('노쇼 남 인원');
            $table->unsignedInteger('no_show_female')
                ->default(0)
                ->after('no_show_male')
                ->comment('노쇼 여 인원');
            $table->json('no_show_platform_counts')
                ->nullable()
                ->after('no_show_female')
                ->comment('플랫폼별 노쇼 수. 예: {"munto":4,"frip":2}');
        });
    }

    public function down(): void
    {
        Schema::table('event_visit_snapshots', function (Blueprint $table) {
            $table->dropColumn([
                'no_show_total',
                'no_show_male',
                'no_show_female',
                'no_show_platform_counts',
            ]);
        });
    }
};
