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
        if (! Schema::hasTable('event_rounds')) {
            Schema::create('event_rounds', function (Blueprint $table) {
                $table->id();
                $table->string('title', 100)->comment('행사명');
                $table->string('type', 10)->default('wine')->comment('타입 (wine: 와인파티, one: 1:1)');
                $table->string('status', 1)->default('P')->comment('상태 (P: 대기, S: 진행, V: 투표중, X: 종료)');
                $table->dateTime('event_time')->comment('행사 시간');
                $table->dateTime('voting_opened_at')->nullable()->comment('투표(V)가 열린 시각 (null: 아직 열리지 않음)');

                $table->timestamps();

                $table->comment('행사 관리 테이블');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_rounds');
    }
};
