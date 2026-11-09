<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * event_rosters 에 "체크인 때 지정한 기준(홈) 테이블" rotation_table_id 를 추가한다.
 *
 * - 옵션A: 관리자가 체크인(번호표 부여) 시 테이블까지 함께 지정 → 그 즉시 성비에 반영.
 * - 참가자 입장등록 시 테이블을 직접 고르지 않고, 이 값으로 좌석(rotation_seats)이 자동 생성된다.
 * - nullable: 아직 테이블을 지정하지 않았거나 과거 데이터 호환.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_rosters', function (Blueprint $table) {
            $table->unsignedBigInteger('rotation_table_id')
                ->nullable()
                ->after('ticket_no')
                ->comment('체크인 때 지정한 기준(홈) 테이블 (rotation_tables.id)');
        });
    }

    public function down(): void
    {
        Schema::table('event_rosters', function (Blueprint $table) {
            $table->dropColumn('rotation_table_id');
        });
    }
};
