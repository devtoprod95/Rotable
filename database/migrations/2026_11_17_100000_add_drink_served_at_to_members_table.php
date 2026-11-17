<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * members 테이블에 "관리자가 음료를 가져다준 시각"을 더한다. (1:1 소개팅 음료 선택)
 *
 * - null 이면 아직 전달 전, 값이 있으면 전달 완료.
 * - 회원의 음료가 바뀌거나(관리자 수정) 음료가 목록에서 삭제되면 null 로 되돌린다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->timestamp('drink_served_at')->nullable()->after('event_drink_id')->comment('음료 전달 완료 시각 (null=전달 전)');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('drink_served_at');
        });
    }
};
