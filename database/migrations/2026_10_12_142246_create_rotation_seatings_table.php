<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 핵심 기록 테이블. 모든 좌석(남/여 공통)을 턴×테이블×회원 단위로 기록한다.
     * 여성 고정석을 별도 컬럼으로 두지 않으므로, 여성이 중간 턴에 다른 테이블로
     * 옮겨 앉아도 그냥 다음 턴에 다른 rotation_table_id로 기록하면 된다.
     * (round, table)의 행들을 모으면 그 턴의 N:M 조합(3:3, 3:2 등)이 그대로 나온다.
     */
    public function up(): void
    {
        if (! Schema::hasTable('rotation_seatings')) {
            Schema::create('rotation_seatings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rotation_round_id')->comment('rotation_rounds.id (몇 번째 턴)');
                $table->unsignedBigInteger('rotation_table_id')->comment('rotation_tables.id (어느 테이블)');
                $table->unsignedBigInteger('member_id')->comment('members.id (앉은 회원)');

                $table->timestamps();
                $table->comment('로테이션 좌석 기록 — 턴×테이블×회원 (만남 이력의 원천)');

                $table->index('rotation_round_id');
                $table->index('rotation_table_id');
                $table->index('member_id');

                $table->foreign('rotation_round_id')->references('id')->on('rotation_rounds')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('rotation_table_id')->references('id')->on('rotation_tables')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('member_id')->references('id')->on('members')->onUpdate('cascade')->onDelete('cascade');

                // 한 회원은 한 턴에 한 자리에만 앉는다.
                $table->unique(['rotation_round_id', 'member_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rotation_seatings');
    }
};
