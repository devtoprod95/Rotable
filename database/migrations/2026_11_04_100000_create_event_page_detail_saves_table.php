<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 상세페이지(홍보용) "이름 붙여 저장한 목록" 테이블.
 *
 * - event_page_details 는 유형(wine/solo)당 1행(현재 작업본)만 보관한다.
 *   이 테이블은 그와 별개로, 작성한 상세페이지 HTML 전체를 "이름(라벨)"을 붙여
 *   유형별로 여러 개 저장(스냅샷)해 두고 나중에 골라 불러오기 위한 목록이다.
 * - 네이밍 규칙상 행사 도메인 → event_ prefix.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_page_detail_saves', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->comment('상세페이지 유형: wine(와인파티) | solo(1:1 소개팅)');
            $table->string('title', 100)->comment('저장 이름(라벨)');
            $table->longText('html')->nullable()->comment('저장 시점의 상세페이지 HTML 전체');
            $table->string('saved_by')->nullable()->comment('저장한 관리자 이름(admins.name)');
            $table->string('saved_by_username')->nullable()->comment('저장한 관리자 아이디(admins.username)');
            $table->timestamps();

            // 유형별 목록을 최신순으로 자주 조회하므로 (type, id) 조합 인덱스.
            $table->index(['type', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_page_detail_saves');
    }
};
