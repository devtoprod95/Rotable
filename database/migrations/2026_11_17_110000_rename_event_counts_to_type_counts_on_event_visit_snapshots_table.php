<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 방문 통계 스냅샷의 "행사별 참여 인원(event_counts)"을 "행사 타입별 참여 인원(type_counts)"으로 바꾼다.
 *
 * - 행사 하나하나가 아니라 타입(1:1 소개팅 / 와인파티)별 합계를 보여주기 위함.
 *   예: {"one":{"events":3,"total":40,"male":20,"female":20,"platform_counts":{"munto":30,"frip":10}}, "wine":{...}}
 * - 저장 형식이 달라 기존 값(행사별 배열)은 비운다. 다음 자정 스냅샷 또는
 *   `php artisan rotation:snapshot-visit-stats` 재실행으로 다시 채워진다. (원본 회원 데이터로 재집계 가능)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_visit_snapshots', function (Blueprint $table) {
            $table->renameColumn('event_counts', 'type_counts');
        });

        DB::table('event_visit_snapshots')->update(['type_counts' => null]);

        Schema::table('event_visit_snapshots', function (Blueprint $table) {
            $table->json('type_counts')->nullable()->comment('행사 타입별 참여 인원(행사 수/총/남/여/플랫폼별) — 키: one(1:1) | wine(와인파티)')->change();
        });
    }

    public function down(): void
    {
        DB::table('event_visit_snapshots')->update(['type_counts' => null]);

        Schema::table('event_visit_snapshots', function (Blueprint $table) {
            $table->renameColumn('type_counts', 'event_counts');
        });

        Schema::table('event_visit_snapshots', function (Blueprint $table) {
            $table->json('event_counts')->nullable()->comment('행사별 참여 인원(총/남/여/플랫폼별) 배열')->change();
        });
    }
};
