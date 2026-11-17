<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * members 에 "같은 행사 · 같은 성별 · 같은 번호표" 중복 방지 유니크를 건다.
 *
 * - members.name 은 번호표(숫자 문자열)다.
 * - 입장등록 검증(checkRegistrableTicket)과 저장 사이에 다른 요청이 끼어드는 경합(TOCTOU)을 DB가 최종 차단한다.
 * - 소프트 삭제(deleted_at)된 회원도 인덱스에 포함된다. (기존 unique(event_id, phone) 과 같은 동작)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->unique(['event_id', 'gender', 'name'], 'members_event_gender_ticket_unique');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique('members_event_gender_ticket_unique');
        });
    }
};
