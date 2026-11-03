<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 손익분기(BEP) 계산기 저장 테이블.
     * - 관리자가 계산기 화면에 입력한 값(인원·비용 항목·기타 수입·목표 이익·성비 가중치 등)을
     *   이름을 붙여 스냅샷(payload JSON)으로 저장/불러오기/삭제한다.
     * - 행사(event) 도메인의 부속 데이터이므로 `event_` prefix 를 사용한다.
     */
    public function up(): void
    {
        if (! Schema::hasTable('event_bep_estimates')) {
            Schema::create('event_bep_estimates', function (Blueprint $table) {
                $table->id();
                $table->string('name')->comment('저장 이름(견적 라벨)');
                $table->string('type', 10)->default('wine')->comment('행사 타입: wine(와인파티) | one(1:1 소개팅)');
                $table->json('payload')->comment('계산기 입력값 스냅샷(JSON): 인원/비용항목/기타수입/목표이익/성비가중치 등');
                $table->unsignedBigInteger('created_by')->nullable()->comment('admins.id (저장 관리자)');

                $table->timestamps();
                $table->comment('손익분기(BEP) 계산기 저장 견적');

                $table->index('type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_bep_estimates');
    }
};
