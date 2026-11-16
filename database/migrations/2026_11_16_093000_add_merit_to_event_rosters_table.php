<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * event_rosters 에 장점(merit)을 추가한다.
 *
 * - 체크인 때 받은 장점을 상세페이지 명단(끌어오기)에서 표로 보여주기 위함.
 * - nullable: 기존 명단·미입력 호환.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_rosters', function (Blueprint $table) {
            $table->string('merit', 100)
                ->nullable()
                ->after('job')
                ->comment('장점');
        });
    }

    public function down(): void
    {
        Schema::table('event_rosters', function (Blueprint $table) {
            $table->dropColumn('merit');
        });
    }
};
