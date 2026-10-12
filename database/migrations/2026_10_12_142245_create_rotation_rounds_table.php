<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('rotation_rounds')) {
            Schema::create('rotation_rounds', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('event_id')->comment('event_rounds.id (행사 회차)');
                $table->unsignedSmallInteger('round_no')->comment('로테이션 턴 순번 (0턴, 1턴, 2턴...)');

                $table->timestamps();
                $table->comment('로테이션 턴(라운드) — 참가자가 이 단위로 테이블을 이동');

                $table->index('event_id');

                $table->foreign('event_id')->references('id')->on('event_rounds')->onUpdate('cascade')->onDelete('cascade');

                $table->unique(['event_id', 'round_no']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rotation_rounds');
    }
};
