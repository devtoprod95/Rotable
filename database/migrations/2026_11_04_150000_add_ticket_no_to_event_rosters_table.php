<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 체크인 명단(event_rosters)에 번호표(ticket_no)를 추가한다.
 *
 * - 호스트가 체크인할 때 부여하는 번호표 숫자. 수동 입력이 1순위, 비우면 자동 배정이 2순위.
 * - 번호표는 같은 성별끼리만 겹치지 않는다. (남 3번 · 여 3번은 공존 가능)
 *   → (event_id, gender, ticket_no) 유니크. 미배정(NULL)은 여러 행이 있어도 충돌하지 않는다.
 * - 입장등록은 이 번호표가 배정된(체크인된) 번호로만 가능하다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_rosters', function (Blueprint $table) {
            $table->unsignedSmallInteger('ticket_no')
                ->nullable()
                ->after('platform')
                ->comment('번호표 숫자(같은 행사·같은 성별 안에서 유일). NULL=미배정');

            $table->unique(['event_id', 'gender', 'ticket_no'], 'event_rosters_event_gender_ticket_unique');
        });
    }

    public function down(): void
    {
        Schema::table('event_rosters', function (Blueprint $table) {
            $table->dropUnique('event_rosters_event_gender_ticket_unique');
            $table->dropColumn('ticket_no');
        });
    }
};
