<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 회원 → 관리자 "요청하기" 테이블.
     * - 회원이 운영진(관리자)에게 보내는 요청/문의 메시지.
     * - read_at(처리/확인 시각)으로 미확인 개수(관리자 플로팅 뱃지)를 판정한다.
     */
    public function up(): void
    {
        if (! Schema::hasTable('admin_requests')) {
            Schema::create('admin_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('event_id')->comment('event_rounds.id (어느 행사에서의 요청인지)');
                $table->unsignedBigInteger('member_id')->comment('members.id (요청한 회원)');
                $table->unsignedSmallInteger('table_no')->nullable()->comment('요청 시점의 회원 테이블 번호 (스냅샷, 미배정 시 null)');
                $table->text('body')->comment('요청 내용');
                $table->timestamp('read_at')->nullable()->comment('관리자가 확인/처리한 시각 (null: 미확인)');

                $table->timestamps();
                $table->comment('회원 → 관리자 요청하기');

                $table->index('event_id');
                $table->index('member_id');
                $table->index(['read_at']);

                $table->foreign('event_id')->references('id')->on('event_rounds')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('member_id')->references('id')->on('members')->onUpdate('cascade')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_requests');
    }
};
