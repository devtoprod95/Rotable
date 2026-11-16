<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 행사별 음료 선택지 테이블. (1:1 소개팅에서 참가자가 고르는 음료 목록)
 *
 * - 관리자가 행사 상세에서 목록을 설정한다.
 * - 같은 행사 안에서 음료 이름은 중복될 수 없다.
 * - sort_order 로 관리자가 입력한 순서를 유지한다.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('event_drinks')) {
            Schema::create('event_drinks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('event_id')->comment('event_rounds.id (어느 행사의 음료인지)');
                $table->string('name', 50)->comment('음료 이름');
                $table->unsignedSmallInteger('sort_order')->default(0)->comment('노출 순서 (작을수록 먼저)');

                $table->timestamps();
                $table->comment('행사별 음료 선택지 (1:1 소개팅)');

                $table->unique(['event_id', 'name'], 'event_drinks_event_name_unique');

                $table->foreign('event_id')->references('id')->on('event_rounds')->onUpdate('cascade')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_drinks');
    }
};
