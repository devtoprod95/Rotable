<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 호스트 발표 멘트(대본) 저장 테이블.
 * - 유형(type)별로 1행만 둔다. (wine=와인파티 / solo=1:1 소개팅)
 * - 멘트는 섹션 구조(JSON)로 저장한다: [{ "no":"①", "heading":"...", "lines":["...", ...] }, ...]
 * - 저장된 행이 없으면 공개 페이지는 코드의 기본 멘트(ScriptService 상수)를 사용한다.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('event_scripts')) {
            Schema::create('event_scripts', function (Blueprint $table) {
                $table->id();
                $table->string('type', 20)->unique()->comment('발표 멘트 유형: wine(와인파티) | solo(1:1 소개팅)');
                $table->string('title')->nullable()->comment('멘트 제목 (예: 와인파티 발표 멘트)');
                $table->json('sections')->nullable()->comment('섹션 구조 JSON: [{no,heading,lines[]}]');
                $table->string('saved_by')->nullable()->comment('마지막으로 저장한 관리자 이름(admins.name)');
                $table->timestamp('saved_at')->nullable()->comment('마지막 저장 시각');

                $table->timestamps();
                $table->comment('호스트 발표 멘트(대본) — 유형별 1행');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_scripts');
    }
};
