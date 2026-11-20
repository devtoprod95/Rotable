<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 행사별 비용 항목 테이블. (대관비 · 음료/다과 · 스태프 등)
 *
 * - 매출 화면에서 순이익 = 실수입 - 비용 합계 를 계산할 때 쓴다.
 * - sort_order 로 관리자가 입력한 순서를 유지한다.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('event_costs')) {
            Schema::create('event_costs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('event_id')->comment('event_rounds.id (어느 행사의 비용인지)');
                $table->string('name', 50)->comment('비용 항목명');
                $table->unsignedInteger('amount')->default(0)->comment('금액(원)');
                $table->unsignedSmallInteger('sort_order')->default(0)->comment('노출 순서 (작을수록 먼저)');

                $table->timestamps();
                $table->comment('행사별 비용 항목');

                $table->index('event_id', 'event_costs_event_index');
                $table->foreign('event_id')->references('id')->on('event_rounds')->onUpdate('cascade')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_costs');
    }
};
