<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 로테이션 좌석 구조 전환 (턴별 좌석 복제 → 회원별 기준자리 + 턴 공식 계산).
 *
 * 변경 개요
 *  - event_rounds.current_round 추가 : 현재 로테이션 턴(0=시작 전, 1,2,3...).
 *  - rotation_seats 신설 : 회원당 "기준(홈) 자리" 1행 (event_id, member_id, rotation_table_id).
 *      · 여자는 이 테이블에 고정, 남자는 1턴 출발 테이블.
 *      · t턴 자리는 저장하지 않고 current_round 와 이 기준자리로 계산한다.
 *  - 기존 rotation_seatings / rotation_rounds 는 더 이상 쓰지 않으므로 제거.
 *      (턴별 좌석을 저장하지 않으므로 round 테이블 자체가 불필요)
 *
 * 운영 데이터가 없는 상태에서 적용한다는 전제. 과거 턴 좌석은 보존하지 않는다.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) event_rounds.current_round 추가
        if (! Schema::hasColumn('event_rounds', 'current_round')) {
            Schema::table('event_rounds', function (Blueprint $table) {
                $table->unsignedSmallInteger('current_round')
                    ->default(0)
                    ->after('voting_opened_at')
                    ->comment('현재 로테이션 턴 (0: 시작 전, 1,2,3...). 턴별 좌석은 이 값과 기준자리로 계산');
            });
        }

        // 2) rotation_seats 신설 (회원당 기준자리 1행)
        if (! Schema::hasTable('rotation_seats')) {
            Schema::create('rotation_seats', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('event_id')->comment('event_rounds.id (행사 회차)');
                $table->unsignedBigInteger('member_id')->comment('members.id (앉은 회원)');
                $table->unsignedBigInteger('rotation_table_id')->comment('rotation_tables.id (기준 테이블: 여자=고정석, 남자=1턴 출발석)');

                $table->timestamps();
                $table->comment('로테이션 기준(홈) 좌석 — 회원당 1행. 턴별 자리는 event_rounds.current_round 로 계산');

                $table->index('event_id');
                $table->index('member_id');
                $table->index('rotation_table_id');

                $table->foreign('event_id')->references('id')->on('event_rounds')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('member_id')->references('id')->on('members')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('rotation_table_id')->references('id')->on('rotation_tables')->onUpdate('cascade')->onDelete('cascade');

                // 한 행사에서 회원은 기준 자리 하나만 갖는다.
                $table->unique(['event_id', 'member_id']);
            });
        }

        // 3) 구 테이블 제거 (seatings 먼저: rounds 를 FK 로 참조)
        Schema::dropIfExists('rotation_seatings');
        Schema::dropIfExists('rotation_rounds');
    }

    public function down(): void
    {
        // 구조 복구 (rollback). 데이터는 복원하지 않는다.
        if (! Schema::hasTable('rotation_rounds')) {
            Schema::create('rotation_rounds', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('event_id')->comment('event_rounds.id (행사 회차)');
                $table->unsignedSmallInteger('round_no')->comment('로테이션 턴 순번 (0턴, 1턴, 2턴...)');
                $table->timestamps();
                $table->comment('로테이션 턴(라운드)');
                $table->index('event_id');
                $table->foreign('event_id')->references('id')->on('event_rounds')->onUpdate('cascade')->onDelete('cascade');
                $table->unique(['event_id', 'round_no']);
            });
        }

        if (! Schema::hasTable('rotation_seatings')) {
            Schema::create('rotation_seatings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rotation_round_id')->comment('rotation_rounds.id (몇 번째 턴)');
                $table->unsignedBigInteger('rotation_table_id')->comment('rotation_tables.id (어느 테이블)');
                $table->unsignedBigInteger('member_id')->comment('members.id (앉은 회원)');
                $table->timestamps();
                $table->comment('로테이션 좌석 기록 — 턴×테이블×회원');
                $table->index('rotation_round_id');
                $table->index('rotation_table_id');
                $table->index('member_id');
                $table->foreign('rotation_round_id')->references('id')->on('rotation_rounds')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('rotation_table_id')->references('id')->on('rotation_tables')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('member_id')->references('id')->on('members')->onUpdate('cascade')->onDelete('cascade');
                $table->unique(['rotation_round_id', 'member_id']);
            });
        }

        Schema::dropIfExists('rotation_seats');

        if (Schema::hasColumn('event_rounds', 'current_round')) {
            Schema::table('event_rounds', function (Blueprint $table) {
                $table->dropColumn('current_round');
            });
        }
    }
};
