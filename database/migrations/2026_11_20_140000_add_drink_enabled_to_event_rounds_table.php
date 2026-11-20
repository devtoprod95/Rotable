<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * event_rounds 테이블에 "음료 선택" on/off 토글(drink_enabled)을 추가한다.
 *
 * - true 이면 1:1 소개팅에서 입장등록 시 음료를 고르고, 관리자 화면에 음료 현황이 노출된다.
 * - false 이면 참가자가 알아서 가져가는 행사로 보고, 입장등록·관리자 화면 모두에서 음료 선택을 숨긴다.
 * - 기본값은 false(꺼짐). 필요한 행사만 관리자가 켠다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_rounds', function (Blueprint $table) {
            $table->boolean('drink_enabled')
                ->default(false)
                ->after('review_gate_enabled')
                ->comment('음료 선택 사용 여부(1:1 소개팅): true=입장등록에서 음료 선택, false=선택 단계 숨김 (기본 꺼짐)');
        });
    }

    public function down(): void
    {
        Schema::table('event_rounds', function (Blueprint $table) {
            $table->dropColumn('drink_enabled');
        });
    }
};
