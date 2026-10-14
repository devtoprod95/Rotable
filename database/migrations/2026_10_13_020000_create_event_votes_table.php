<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 회원 간 투표(최종 선택) 테이블.
     * - 투표중(V) 단계에서 회원이 마음에 든 이성에게 투표한다. (회원당 최대 3명)
     * - voter_id 와 target_id 가 서로를 지목하면 "상호 매칭"으로 간주한다.
     * - (event_id, voter_id, target_id) 조합은 유일하다. (같은 상대 중복 투표 방지)
     */
    public function up(): void
    {
        if (! Schema::hasTable('event_votes')) {
            Schema::create('event_votes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('event_id')->comment('event_rounds.id (어느 행사에서의 투표인지)');
                $table->unsignedBigInteger('voter_id')->comment('members.id (투표한 회원)');
                $table->unsignedBigInteger('target_id')->comment('members.id (지목당한 회원)');

                $table->timestamps();
                $table->comment('회원 간 투표 (최종 선택)');

                $table->index('event_id');
                $table->index('voter_id');
                $table->index('target_id');
                $table->unique(['event_id', 'voter_id', 'target_id'], 'event_votes_event_voter_target_unique');

                $table->foreign('event_id')->references('id')->on('event_rounds')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('voter_id')->references('id')->on('members')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('target_id')->references('id')->on('members')->onUpdate('cascade')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_votes');
    }
};
